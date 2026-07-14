<?php

namespace App\Controllers\Bikes\Api;

use App\Controllers\BaseController;
use App\Models\BikeNoteMediaModel;
use App\Models\BikeNoteModel;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;

class BikeNoteMedia extends BaseController
{
    private const ALLOWED_TYPES = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/gif'       => 'gif',
        'image/webp'      => 'webp',
        'video/mp4'       => 'mp4',
        'application/pdf' => 'pdf',
    ];

    private const MAX_FILE_SIZE = 524288000; // 500 MB
    private const MAX_WIDTH     = 1920;

    /**
     * POST /api/bikes/:bikeId/notes/:noteId/media
     */
    public function upload(int $bikeId, int $noteId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (! (new BikeNoteModel())->where('bike_id', $bikeId)->find($noteId)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Note not found.']);
        }

        $file = $this->request->getFile('media');

        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'No file uploaded.']);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'File exceeds the maximum allowed size (500 MB).']);
        }

        $mime = $file->getMimeType();
        if (! array_key_exists($mime, self::ALLOWED_TYPES)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP, MP4, PDF.']);
        }

        $ext      = self::ALLOWED_TYPES[$mime];
        $uuid     = Uuid::uuid4()->toString();
        $filename = $uuid . '.' . $ext;
        $destDir  = FCPATH . 'uploads/bikes/notes/media/';

        if (! is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . $filename;
        $isImage  = str_starts_with($mime, 'image/');
        $width    = null;
        $height   = null;

        if ($isImage) {
            $tmpName  = $file->getTempName();
            $sizeInfo = @getimagesize($tmpName);

            if (! $sizeInfo) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Unable to read image.']);
            }

            [$width, $height] = [$sizeInfo[0], $sizeInfo[1]];

            if ($width > self::MAX_WIDTH) {
                $newWidth  = self::MAX_WIDTH;
                $newHeight = (int) round($height * ($newWidth / $width));

                try {
                    $this->resizeImage($tmpName, $destPath, $mime, $width, $height, $newWidth, $newHeight);
                    $width  = $newWidth;
                    $height = $newHeight;
                } catch (\Exception) {
                    return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to resize image.']);
                }
            } else {
                try {
                    $file->move($destDir, $filename);
                } catch (\Exception) {
                    return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save uploaded file.']);
                }
            }
        } else {
            try {
                $file->move($destDir, $filename);
            } catch (\Exception) {
                return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save uploaded file.']);
            }
        }

        $mediaModel = new BikeNoteMediaModel();
        $maxOrder   = $mediaModel->where('bike_note_id', $noteId)->selectMax('sort_order', 'max_order')->first();
        $sortOrder  = ($maxOrder['max_order'] ?? -1) + 1;

        $mediaId = $mediaModel->insert([
            'bike_note_id' => $noteId,
            'file_name'    => $filename,
            'file_ext'     => $ext,
            'mime_type'    => $mime,
            'width'        => $width,
            'height'       => $height,
            'filesize'     => @filesize($destPath) ?: null,
            'sort_order'   => $sortOrder,
        ], true);

        return $this->response->setStatusCode(201)->setJSON([
            'status' => 'success',
            'media'  => [
                'id'        => $mediaId,
                'file_name' => $filename,
                'mime_type' => $mime,
                'url'       => site_url('uploads/bikes/notes/media/' . $filename),
            ],
        ]);
    }

    /**
     * DELETE /api/bikes/:bikeId/notes/:noteId/media/:mediaId
     */
    public function delete(int $bikeId, int $noteId, int $mediaId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (! (new BikeNoteModel())->where('bike_id', $bikeId)->find($noteId)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Note not found.']);
        }

        $mediaModel = new BikeNoteMediaModel();
        $media      = $mediaModel->where('bike_note_id', $noteId)->find($mediaId);

        if (! $media) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Media not found.']);
        }

        $path = FCPATH . 'uploads/bikes/notes/media/' . $media['file_name'];
        if (is_file($path)) {
            @unlink($path);
        }

        $mediaModel->delete($mediaId);

        return $this->response->setJSON(['status' => 'success', 'deleted' => $mediaId]);
    }

    /**
     * POST /api/bikes/:bikeId/notes/:noteId/media/reorder
     */
    public function reorder(int $bikeId, int $noteId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (! (new BikeNoteModel())->where('bike_id', $bikeId)->find($noteId)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Note not found.']);
        }

        $json = $this->request->getJSON(true);
        $ids  = array_map('intval', $json['ids'] ?? []);

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'No media IDs provided.']);
        }

        $mediaModel = new BikeNoteMediaModel();

        foreach ($ids as $index => $mediaId) {
            $mediaModel->where('bike_note_id', $noteId)->where('id', $mediaId)->set(['sort_order' => $index])->update();
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    private function requireAdmin(): ?ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden.']);
        }

        return null;
    }

    private function resizeImage(
        string $src,
        string $dest,
        string $mime,
        int $srcW,
        int $srcH,
        int $dstW,
        int $dstH
    ): void {
        $srcImg = match ($mime) {
            'image/jpeg' => \imagecreatefromjpeg($src),
            'image/png'  => \imagecreatefrompng($src),
            'image/webp' => \imagecreatefromwebp($src),
            'image/gif'  => \imagecreatefromgif($src),
            default      => false,
        };

        if (! $srcImg) {
            throw new \RuntimeException('GD could not open source image.');
        }

        $dstImg = \imagecreatetruecolor($dstW, $dstH);

        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            if (function_exists('imagealphablend')) {
                \imagealphablend($dstImg, false);
            }
            \imagesavealpha($dstImg, true);
            $transparent = \imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            \imagefill($dstImg, 0, 0, $transparent);
        }

        \imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        match ($mime) {
            'image/jpeg' => \imagejpeg($dstImg, $dest, 85),
            'image/png'  => \imagepng($dstImg, $dest),
            'image/webp' => \imagewebp($dstImg, $dest, 85),
            'image/gif'  => \imagegif($dstImg, $dest),
            default      => null,
        };

        \imagedestroy($srcImg);
        \imagedestroy($dstImg);
    }
}
