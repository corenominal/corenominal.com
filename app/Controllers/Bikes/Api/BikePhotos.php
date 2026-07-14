<?php

namespace App\Controllers\Bikes\Api;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\BikePhotoModel;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;

class BikePhotos extends BaseController
{
    private const ALLOWED_MIME_TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
    private const MAX_WIDTH          = 1920;

    /**
     * POST /api/bikes/:bikeId/photos
     */
    public function upload(int $bikeId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (! (new BikeModel())->find($bikeId)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Bike not found.']);
        }

        $file = $this->request->getFile('photo');

        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'No file uploaded.']);
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid file type. Allowed: png, jpeg, webp, gif.']);
        }

        $tmpName  = $file->getTempName();
        $sizeInfo = @getimagesize($tmpName);
        if (! $sizeInfo) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Unable to read image.']);
        }

        [$width, $height] = [$sizeInfo[0], $sizeInfo[1]];

        $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $uuid     = Uuid::uuid4()->toString();
        $filename = $uuid . '.' . $ext;
        $destDir  = FCPATH . 'uploads/bikes/media/';

        if (! is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . $filename;

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

        $photoModel = new BikePhotoModel();
        $maxOrder   = $photoModel->where('bike_id', $bikeId)->selectMax('sort_order', 'max_order')->first();
        $sortOrder  = ($maxOrder['max_order'] ?? -1) + 1;

        $photoId = $photoModel->insert([
            'bike_id'    => $bikeId,
            'file_name'  => $filename,
            'file_ext'   => $ext,
            'mime_type'  => $mime,
            'width'      => $width,
            'height'     => $height,
            'filesize'   => @filesize($destPath) ?: null,
            'sort_order' => $sortOrder,
        ], true);

        return $this->response->setStatusCode(201)->setJSON([
            'status' => 'success',
            'photo'  => [
                'id'        => $photoId,
                'file_name' => $filename,
                'url'       => site_url('uploads/bikes/media/' . $filename),
            ],
        ]);
    }

    /**
     * DELETE /api/bikes/:bikeId/photos/:photoId
     */
    public function delete(int $bikeId, int $photoId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $photoModel = new BikePhotoModel();
        $photo      = $photoModel->where('bike_id', $bikeId)->find($photoId);

        if (! $photo) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Photo not found.']);
        }

        $path = FCPATH . 'uploads/bikes/media/' . $photo['file_name'];
        if (is_file($path)) {
            @unlink($path);
        }

        $photoModel->delete($photoId);

        return $this->response->setJSON(['status' => 'success', 'deleted' => $photoId]);
    }

    /**
     * POST /api/bikes/:bikeId/photos/reorder
     */
    public function reorder(int $bikeId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $json = $this->request->getJSON(true);
        $ids  = array_map('intval', $json['ids'] ?? []);

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'No photo IDs provided.']);
        }

        $photoModel = new BikePhotoModel();

        foreach ($ids as $index => $photoId) {
            $photoModel->where('bike_id', $bikeId)->where('id', $photoId)->set(['sort_order' => $index])->update();
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
            \imagealphablend($dstImg, false);
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
