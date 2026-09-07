<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case RECRUITER = 'recruiter';
    case CANDIDATE = 'candidate';
    
    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::MANAGER => 'Gerente',
            self::RECRUITER => 'Recrutador',
            self::CANDIDATE => 'Candidato',
        };
    }
}
