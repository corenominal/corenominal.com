<?php

namespace App\Controllers\Auth\Admin;

use App\Models\GroupModel;
use App\Models\UserModel;
use Ramsey\Uuid\Uuid;

class Groups extends BaseController
{
    public function index()
    {
        $data['title']            = 'Auth Admin Groups';
        $data['js']               = ['auth/admin/groups'];
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'auth/admin/sidebar-menu';
        return view('auth/admin/groups', $data);
    }

    public function getData()
    {
        $model = new GroupModel();

        $page    = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = in_array((int)($this->request->getGet('per_page') ?? 20), [10, 20, 50, 100])
                   ? (int)$this->request->getGet('per_page')
                   : 20;
        $search  = trim((string)($this->request->getGet('search') ?? ''));
        $sortCol = $this->request->getGet('sort') ?? 'id';
        $sortDir = strtolower((string)($this->request->getGet('dir') ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $allowed = ['id', 'group', 'created_at'];
        if (!in_array($sortCol, $allowed)) {
            $sortCol = 'id';
        }

        if ($search !== '') {
            $model->groupStart()
                  ->like('group', $search)
                  ->orLike('uuid', $search)
                  ->orLike('user_uuid', $search)
                  ->groupEnd();
        }

        $total  = $model->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $groups = $model->select('groups.id, groups.user_uuid, groups.group, groups.created_at, users.email as user_email')
                        ->join('users', 'users.uuid = groups.user_uuid', 'left')
                        ->orderBy($sortCol, $sortDir)
                        ->findAll($perPage, $offset);

        return $this->response->setJSON([
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int)ceil($total / $perPage) : 0,
            'groups'   => $groups,
        ]);
    }

    public function getGroup(int $id)
    {
        $model = new GroupModel();
        $group = $model->select('id, uuid, user_uuid, group, created_at')
                       ->find($id);

        if (!$group) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Group not found.']);
        }

        return $this->response->setJSON($group);
    }

    public function getGroupNames()
    {
        $model = new GroupModel();
        $rows  = $model->select('group')
                       ->distinct()
                       ->orderBy('group', 'ASC')
                       ->findAll();

        return $this->response->setJSON([
            'groups' => array_column($rows, 'group'),
        ]);
    }

    public function getUsers()
    {
        $model = new UserModel();
        $users = $model->select('uuid, email')
                       ->orderBy('email', 'ASC')
                       ->findAll();

        return $this->response->setJSON(['users' => $users]);
    }

    public function createGroup()
    {
        $name     = trim((string)($this->request->getPost('group') ?? ''));
        $userUuid = trim((string)($this->request->getPost('user_uuid') ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Group name is required.']);
        }

        $model = new GroupModel();

        if ($userUuid !== '' && $model->where('user_uuid', $userUuid)->where('group', $name)->first()) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'This user is already a member of that group.']);
        }

        $model->insert([
            'uuid'      => Uuid::uuid4()->toString(),
            'user_uuid' => $userUuid !== '' ? $userUuid : null,
            'group'     => $name,
        ]);

        logit("Admin created group \"{$name}\".");

        return $this->response->setJSON(['success' => true]);
    }

    public function updateGroup(int $id)
    {
        $model = new GroupModel();

        if (!$model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Group not found.']);
        }

        $name     = trim((string)($this->request->getPost('group') ?? ''));
        $userUuid = trim((string)($this->request->getPost('user_uuid') ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Group name is required.']);
        }

        if ($userUuid !== '' && $model->where('user_uuid', $userUuid)->where('group', $name)->where('id !=', $id)->first()) {
            return $this->response->setStatusCode(409)->setJSON(['error' => 'This user is already a member of that group.']);
        }

        $model->update($id, [
            'group'     => $name,
            'user_uuid' => $userUuid !== '' ? $userUuid : null,
        ]);

        logit("Admin updated group ID {$id} (\"{$name}\").");

        return $this->response->setJSON(['success' => true]);
    }

    public function deleteGroup(int $id)
    {
        $model = new GroupModel();

        $group = $model->select('id, group')->find($id);
        if (!$group) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Group not found.']);
        }

        $model->delete($id);

        logit("Admin deleted group ID {$id} (\"{$group['group']}\").", 1);

        return $this->response->setJSON(['success' => true]);
    }

    public function bulkDelete()
    {
        $ids = $this->request->getPost('ids');

        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No IDs provided.']);
        }

        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid IDs provided.']);
        }

        $model = new GroupModel();
        $model->delete($ids);

        logit('Admin bulk deleted ' . count($ids) . ' group(s): IDs ' . implode(', ', $ids) . '.', 1);

        return $this->response->setJSON(['success' => true, 'deleted' => count($ids)]);
    }
}
