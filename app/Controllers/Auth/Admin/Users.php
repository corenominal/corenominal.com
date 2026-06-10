<?php
namespace App\Controllers\Auth\Admin;

use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        $data['title']           = 'Auth Admin Users';
        $data['js']              = ['auth/admin/users'];
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu']    = 'auth/admin/sidebar-menu';
        return view('auth/admin/users', $data);
    }

    public function getData()
    {
        $model = new UserModel();

        $page    = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = in_array((int)($this->request->getGet('per_page') ?? 20), [10, 20, 50, 100])
                   ? (int)$this->request->getGet('per_page')
                   : 20;
        $search  = trim((string)($this->request->getGet('search') ?? ''));
        $sortCol = $this->request->getGet('sort') ?? 'id';
        $sortDir = strtolower((string)($this->request->getGet('dir') ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $allowed = ['id', 'email', 'username', 'realname', 'validated', 'banned', 'created_at'];
        if (!in_array($sortCol, $allowed)) {
            $sortCol = 'id';
        }

        if ($search !== '') {
            $model->groupStart()
                  ->like('email', $search)
                  ->orLike('username', $search)
                  ->orLike('realname', $search)
                  ->groupEnd();
        }

        $total  = $model->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $users = $model->select('id, uuid, email, username, realname, validated, banned, created_at')
                       ->orderBy($sortCol, $sortDir)
                       ->findAll($perPage, $offset);

        return $this->response->setJSON([
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int)ceil($total / $perPage) : 0,
            'users'    => $users,
        ]);
    }

    public function getUser($id)
    {
        $model = new UserModel();
        $user  = $model->select('id, uuid, email, username, realname, validated, banned, created_at')
                       ->find((int)$id);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found.']);
        }

        return $this->response->setJSON($user);
    }

    public function updateUser($id)
    {
        $model = new UserModel();
        $id    = (int)$id;

        if (!$model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found.']);
        }

        $email = trim((string)($this->request->getPost('email') ?? ''));
        if ($email === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Email is required.']);
        }

        $duplicate = $model->where('email', $email)->where('id !=', $id)->first();
        if ($duplicate) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'Email is already in use.']);
        }

        $model->update($id, [
            'email'     => $email,
            'username'  => trim((string)($this->request->getPost('username') ?? '')),
            'realname'  => trim((string)($this->request->getPost('realname') ?? '')),
            'validated' => (int)(bool)$this->request->getPost('validated'),
            'banned'    => (int)(bool)$this->request->getPost('banned'),
        ]);

        logit("Admin updated user ID {$id} ({$email}).");

        return $this->response->setJSON(['success' => true]);
    }

    public function deleteUser($id)
    {
        $model = new UserModel();
        $id    = (int)$id;

        $user = $model->select('id, email')->find($id);
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'User not found.']);
        }

        $model->delete($id);

        logit("Admin deleted user ID {$id} ({$user['email']}).", 1);

        return $this->response->setJSON(['success' => true]);
    }

    public function bulkDelete()
    {
        $ids = $this->request->getPost('ids');

        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No IDs provided.']);
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid IDs provided.']);
        }

        $model = new UserModel();
        $model->delete($ids);

        logit('Admin bulk deleted ' . count($ids) . ' user(s): IDs ' . implode(', ', $ids) . '.', 1);

        return $this->response->setJSON(['success' => true, 'deleted' => count($ids)]);
    }
}
