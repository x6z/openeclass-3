<?php

namespace modules\tc\Zoom\User;

class ZoomUser
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $type;
    /**
     * @var ZoomUserRepository
     */
    private $zoomUserRepository;

    public function __construct(ZoomUserRepository $zoomUserRepository)
    {
        $this->zoomUserRepository = $zoomUserRepository;
    }

    public function create($eclassUser): ?ZoomUser
    {
        $user = $this->zoomUserRepository->createZoomUser($eclassUser);
        return $this->setUserAttributes($user);
    }

    public function get(int $id): ?ZoomUser
    {
        $dbUser = $this->zoomUserRepository->getUserFromDatabase($id);
        return $this->setUserAttributes($dbUser);
    }

    private function setZoomId(string $id)
    {
        $this->id = $id;
    }

    private function setFirstName(string $first_name)
    {
        $this->first_name = $first_name;
    }

    private function setLastName(string $last_name)
    {
        $this->last_name = $last_name;
    }

    private function setEmail(string $email)
    {
        $this->email = $email;
    }

    private function setType(string $type)
    {
        $this->type = $type;
    }

    private function setUserAttributes ($user): ?ZoomUser
    {
        if (
            !$user
            || empty($user->id)
            || empty($user->first_name)
            || empty($user->last_name)
            || empty($user->email)
            || empty($user->type)
        ) {
            return null;
        }
        $this->setZoomId($user->id);
        $this->setFirstName($user->first_name);
        $this->setLastName($user->last_name);
        $this->setEmail($user->email);
        $this->setType($user->type);

        return $this;
    }
}