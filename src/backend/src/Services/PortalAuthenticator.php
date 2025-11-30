<?php

/**
 * Developer: Adugna Gizaw
 * Phone: +251911144168
 * Email: gizawadugna@gmail.com
 */

namespace App\Services;

use App\Repositories\MemberRepository;
use App\Repositories\StaffRepository;
use PDO;

class PortalAuthenticator
{
    private ?MemberRepository $memberRepository = null;
    private ?StaffRepository $staffRepository = null;

    public function __construct(private PDO $pdo)
    {
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function authenticateMember(string $studentId, string $password): ?array
    {
        $studentId = strtoupper(trim($studentId));
        if ($studentId === '' || $password === '') {
            return null;
        }

        $member = $this->memberRepository()->findByStudentId($studentId);
        if (! $member || ! $this->verifyHash($member['password_hash'] ?? null, $password)) {
            return null;
        }

        $this->rehashIfNeeded('member', (int) $member['id'], $password, $member['password_hash'] ?? null);

        return $member;
    }

    public function authenticateStaff(string $employeeId, string $password): ?array
    {
        $employeeId = strtoupper(trim($employeeId));
        if ($employeeId === '' || $password === '') {
            return null;
        }

        $staff = $this->staffRepository()->findByEmployeeId($employeeId);
        if (! $staff || ! $this->verifyHash($staff['password_hash'] ?? null, $password)) {
            return null;
        }

        $this->rehashIfNeeded('staff', (int) $staff['id'], $password, $staff['password_hash'] ?? null);

        return $staff;
    }

    private function verifyHash(?string $hash, string $password): bool
    {
        if (! $hash) {
            return false;
        }

        return password_verify($password, $hash);
    }

    private function rehashIfNeeded(string $type, int $id, string $password, ?string $hash): void
    {
        if (! $hash || ! password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            return;
        }

        $updatedHash = password_hash($password, PASSWORD_DEFAULT);

        if ($type === 'member') {
            $this->memberRepository()->update($id, ['password_hash' => $updatedHash]);
        } else {
            $this->staffRepository()->update($id, ['password_hash' => $updatedHash]);
        }
    }

    private function memberRepository(): MemberRepository
    {
        return $this->memberRepository ??= new MemberRepository($this->pdo);
    }

    private function staffRepository(): StaffRepository
    {
        return $this->staffRepository ??= new StaffRepository($this->pdo);
    }
}
