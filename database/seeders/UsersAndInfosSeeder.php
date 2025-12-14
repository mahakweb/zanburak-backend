<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersAndInfosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        // Create Admin User
        $admin = User::create([
            'first_name' => 'مدیر',
            'last_name' => 'سیستم',
            'email' => 'admin@zanburak.ir',
            'email_verified_at' => $now,
            'mobile' => '+989123456789',
            'mobile_verified_at' => $now,
            'password' => Hash::make('password'),
            'username' => 'admin',
            'is_superuser' => true,
            'is_staff' => true,
            'active' => true,
            'wallet_balance' => 0,
            'notifications_enabled' => true,
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
        ]);
        $adminId = $admin->id;

        // Create Admin Info
        DB::table('infos')->insert([
            'user_id' => $adminId,
            'birth_date' => '1990-01-01',
            'job' => 'مدیر سیستم',
            'about' => 'مدیر کل سیستم',
            'website' => 'https://zanburak.ir',
            'github' => 'https://github.com/zanburak',
            'linkedin' => 'https://linkedin.com/company/zanburak',
            'telegram' => '@zanburak',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Assign administrator role to admin user
        $adminRole = DB::table('roles')->where('name', 'administrator')->first();
        if ($adminRole) {
            DB::table('role_user')->insert([
                'role_id' => $adminRole->id,
                'user_id' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create Teacher User
        $teacher = User::create([
            'first_name' => 'علی',
            'last_name' => 'احمدی',
            'email' => 'teacher@zanburak.ir',
            'email_verified_at' => $now,
            'mobile' => '+989123456790',
            'mobile_verified_at' => $now,
            'password' => Hash::make('password'),
            'username' => 'teacher',
            'is_superuser' => false,
            'is_staff' => false,
            'active' => true,
            'wallet_balance' => 0,
            'notifications_enabled' => true,
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
        ]);
        $teacherId = $teacher->id;

        // Create Teacher Info
        DB::table('infos')->insert([
            'user_id' => $teacherId,
            'birth_date' => '1985-05-15',
            'job' => 'مدرس برنامه‌نویسی',
            'about' => 'مدرس با تجربه در زمینه برنامه‌نویسی وب و موبایل',
            'github' => 'https://github.com/teacher',
            'linkedin' => 'https://linkedin.com/in/teacher',
            'telegram' => '@teacher',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Assign teacher role
        $teacherRole = DB::table('roles')->where('name', 'teacher')->first();
        if ($teacherRole) {
            DB::table('role_user')->insert([
                'role_id' => $teacherRole->id,
                'user_id' => $teacherId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create Student User
        $student = User::create([
            'first_name' => 'محمد',
            'last_name' => 'رضایی',
            'email' => 'student@zanburak.ir',
            'email_verified_at' => $now,
            'mobile' => '+989123456791',
            'mobile_verified_at' => $now,
            'password' => Hash::make('password'),
            'username' => 'student',
            'is_superuser' => false,
            'is_staff' => false,
            'active' => true,
            'wallet_balance' => 100000,
            'notifications_enabled' => true,
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
        ]);
        $studentId = $student->id;

        // Create Student Info
        DB::table('infos')->insert([
            'user_id' => $studentId,
            'birth_date' => '1995-08-20',
            'job' => 'دانشجوی برنامه‌نویسی',
            'about' => 'علاقه‌مند به یادگیری برنامه‌نویسی و توسعه نرم‌افزار',
            'github' => 'https://github.com/student',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Assign student role
        $studentRole = DB::table('roles')->where('name', 'student')->first();
        if ($studentRole) {
            DB::table('role_user')->insert([
                'role_id' => $studentRole->id,
                'user_id' => $studentId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Create Content Admin User
        $contentAdmin = User::create([
            'first_name' => 'فاطمه',
            'last_name' => 'کریمی',
            'email' => 'content@zanburak.ir',
            'email_verified_at' => $now,
            'mobile' => '+989123456792',
            'mobile_verified_at' => $now,
            'password' => Hash::make('password'),
            'username' => 'content_admin',
            'is_superuser' => false,
            'is_staff' => true,
            'active' => true,
            'wallet_balance' => 0,
            'notifications_enabled' => true,
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
        ]);
        $contentAdminId = $contentAdmin->id;

        // Create Content Admin Info
        DB::table('infos')->insert([
            'user_id' => $contentAdminId,
            'birth_date' => '1988-03-10',
            'job' => 'مدیر محتوا',
            'about' => 'مدیریت و تولید محتوای آموزشی',
            'linkedin' => 'https://linkedin.com/in/content',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Assign content_admin role
        $contentAdminRole = DB::table('roles')->where('name', 'content_admin')->first();
        if ($contentAdminRole) {
            DB::table('role_user')->insert([
                'role_id' => $contentAdminRole->id,
                'user_id' => $contentAdminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

