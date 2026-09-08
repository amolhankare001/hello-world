<?php

namespace App\Enums;

enum RoleCode: string
{
    case SuperAdmin = 'SUPER_ADMIN';
    case SchoolAdmin = 'SCHOOL_ADMIN';
    case Mentor = 'MENTOR';
    case Student = 'STUDENT';
}
