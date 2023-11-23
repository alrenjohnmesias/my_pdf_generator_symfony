<?php
namespace App\Message;

class PdfGenerator
{
    private $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getName(): int
    {
        return $this->userId;
    }
}