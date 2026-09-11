<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// class UserSeeder extends Seeder
// {
//     public function run(): void
//     {
//         // Admin default
//         User::updateOrCreate(
//             ['emp_id' => 'I0000'],
//             [
//                 'full_name'         => 'System Administrator',
//                 'password'          => Hash::make('12345'), // ganti di production!
//                 'role_id'           => 1,                         // Administrator
//                 'sub_role'          => [2],                       // contoh: Administrator/HR
//                 'departement_cat'   => 1,                         // IT
//                 'is_active'         => 1,
//                 'is_deleted'        => 0,
//                 'person_process'    => 0,
//                 'is_first_login'    => 1,
//             ]
//         );

//         // Contoh user lain (Instructor)
//         User::updateOrCreate(
//             ['emp_id' => 'I0001'],
//             [
//                 'full_name'       => 'Default Instructor',
//                 'password'        => Hash::make('`12345`'),
//                 'role_id'         => 2,           // Instructor
//                 'sub_role'        => [4],         // Learner
//                 'departement_cat' => 3,           
//                 'is_active'       => 1,
//             ]
//         );
//     }
// }

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['emp_id' => 'I000007', 'full_name' => 'Stefanie Maria'],
            ['emp_id' => 'I000010', 'full_name' => 'Lumindi Faradhlina Dewi'],
            ['emp_id' => 'I000012', 'full_name' => 'Supriyadi Rosdi'],
            ['emp_id' => 'I000018', 'full_name' => 'Yulia Nurlitasari'],
            ['emp_id' => 'I000021', 'full_name' => 'Artistian Nada Putra'],
            ['emp_id' => 'I000026', 'full_name' => 'Sifa Cahayati'],
            ['emp_id' => 'I000034', 'full_name' => 'Muhammad Rizky'],
            ['emp_id' => 'I000036', 'full_name' => 'Muhamad Nur Iskandar'],
            ['emp_id' => 'I000041', 'full_name' => 'Susan Tjahyono'],
            ['emp_id' => 'I000054', 'full_name' => 'Kadek Artawan'],
            ['emp_id' => 'I000059', 'full_name' => 'Sintia Septiani'],
            ['emp_id' => 'I000060', 'full_name' => 'Aldo Alvarizi'],
            ['emp_id' => 'I000063', 'full_name' => 'Nadar Satria'],
            ['emp_id' => 'I000068', 'full_name' => 'Andang Bayu Prakoso'],
            ['emp_id' => 'I000067', 'full_name' => 'Heri Tri Mulyanto'],
            ['emp_id' => 'I000073', 'full_name' => 'Suci Adji'],
            ['emp_id' => 'I000078', 'full_name' => 'Mohamad Fiqi Janhari'],
            ['emp_id' => 'I000089', 'full_name' => 'Rinawuri Trisninurasih'],
            ['emp_id' => 'I000094', 'full_name' => 'Maria Ivayanti'],
            ['emp_id' => 'I000090', 'full_name' => 'Lily Gunarti'],
            ['emp_id' => 'I000108', 'full_name' => 'Erwin Bonatua Hutasoit'],
            ['emp_id' => 'I000113', 'full_name' => 'Freddy W Podung, SE'],
            ['emp_id' => 'I000116', 'full_name' => 'Ketut Elisa Yuda Suksma'],
            ['emp_id' => 'I000117', 'full_name' => 'Denis Atmawijaya'],
            ['emp_id' => 'I000119', 'full_name' => 'Alfian'],
            ['emp_id' => 'I000088', 'full_name' => 'Susanto'],
            ['emp_id' => 'I000268', 'full_name' => 'Ignasius Kukuh Damarjati'],
            ['emp_id' => 'I000269', 'full_name' => 'Dhani Christian Hadiyono P'],
            ['emp_id' => 'I000275', 'full_name' => 'Previanto Pradipta'],
            ['emp_id' => 'I000277', 'full_name' => 'Fitri Yanto Ramdhani'],
            ['emp_id' => 'I000278', 'full_name' => 'Nanda Vindi Aini'],
            ['emp_id' => 'I000126', 'full_name' => 'Abdul Gafur Hi Ali'],
            ['emp_id' => 'I000124', 'full_name' => 'Yehezkiel Putra Purba'],
            ['emp_id' => 'I000127', 'full_name' => 'Dedek Khoerul Umam'],
            ['emp_id' => 'I000128', 'full_name' => 'Marula'],
            ['emp_id' => 'I000130', 'full_name' => 'William'],
            ['emp_id' => 'I000131', 'full_name' => 'Indra Lesmana Sihombing'],
            ['emp_id' => 'I000133', 'full_name' => 'Deny Eka Syaputra'],
            ['emp_id' => 'I000141', 'full_name' => 'Yuris Alfa Toni'],
            ['emp_id' => 'I000149', 'full_name' => 'Rudy Hartanto'],
            ['emp_id' => 'I000152', 'full_name' => 'Rizky Raditia Rachman'],
            ['emp_id' => 'I000153', 'full_name' => 'Nadine Mariska'],
            ['emp_id' => 'I000157', 'full_name' => 'Krisna Cahya Sumirat'],
            ['emp_id' => 'I000159', 'full_name' => 'Tiara Lutviany Prima'],
            ['emp_id' => 'I000160', 'full_name' => 'Bagas Kusuma Syahputra'],
            ['emp_id' => 'I000170', 'full_name' => 'Gladwin Leonard Haryadi'],
            ['emp_id' => 'I000172', 'full_name' => 'Aditya Dwi Rehata'],
            ['emp_id' => 'I000177', 'full_name' => 'Dhea Olivia Ariesta'],
            ['emp_id' => 'I000179', 'full_name' => 'Ayu Muchlisah'],
            ['emp_id' => 'I000180', 'full_name' => 'Bagus Dwi Yuni Kurniawan'],
            ['emp_id' => 'I000182', 'full_name' => 'Dikdik Sidik Pamungkas'],
            ['emp_id' => 'I000192', 'full_name' => 'Reda Fitriana'],
            ['emp_id' => 'I000195', 'full_name' => 'Miska Antika'],
            ['emp_id' => 'I000202', 'full_name' => 'Syarifah Maisyarah'],
            ['emp_id' => 'I000203', 'full_name' => 'Robby Anugrah Saputra'],
            ['emp_id' => 'I000211', 'full_name' => 'Djoni'],
            ['emp_id' => 'I000214', 'full_name' => 'Muhamad Muleyadi'],
            ['emp_id' => 'I000216', 'full_name' => 'Reza Pramudya Putra'],
            ['emp_id' => 'I000219', 'full_name' => 'Trisna Ariakusuma'],
            ['emp_id' => 'I000223', 'full_name' => 'Seto Pramono'],
            ['emp_id' => 'I000225', 'full_name' => 'Annisa Noor Rachma'],
            ['emp_id' => 'I000229', 'full_name' => 'Siti Amalia Arrayyan'],
            ['emp_id' => 'I000231', 'full_name' => 'Agas Radiya'],
            ['emp_id' => 'I000232', 'full_name' => 'Ahmad Tri Yuda'],
            ['emp_id' => 'I000234', 'full_name' => 'Arie Irwanto'],
            ['emp_id' => 'I000240', 'full_name' => 'Sunardi'],
            ['emp_id' => 'I000242', 'full_name' => 'Dwi Septianasari P'],
            ['emp_id' => 'I000250', 'full_name' => 'Tito Fajar Andreanto'],
            ['emp_id' => 'I000265', 'full_name' => 'Muhammad Nur Fuad'],
            ['emp_id' => 'I000267', 'full_name' => 'Dadang Hermawan'],
            ['emp_id' => 'I000286', 'full_name' => 'Rizka Ramdhania Setiadin'],
            ['emp_id' => 'I000287', 'full_name' => 'Suhardi'],
            ['emp_id' => 'I000308', 'full_name' => 'Sugeng Purwanto'],
            ['emp_id' => 'I000288', 'full_name' => 'Fredik Primoda'],
            ['emp_id' => 'I000289', 'full_name' => 'Julian Arifyandi'],
            ['emp_id' => 'I000297', 'full_name' => 'Annisa Dwi Rahmawati'],
            ['emp_id' => 'I000305', 'full_name' => 'Parlin'],
            ['emp_id' => 'I000291', 'full_name' => 'Bambang Muhammad Syafii'],
            ['emp_id' => 'I000298', 'full_name' => 'Ayu Lestari'],
            ['emp_id' => 'I000293', 'full_name' => 'Dedi Rayaman Saragih'],
            ['emp_id' => 'I000302', 'full_name' => 'M. Akbar Ranova'],
            ['emp_id' => 'I000299', 'full_name' => 'Aboy Rifai'],
            ['emp_id' => 'I000310', 'full_name' => 'Andru Bimo Santoso'],
            ['emp_id' => 'I000315', 'full_name' => 'Eka Mahzurul Safitri'],
            ['emp_id' => 'I000320', 'full_name' => 'Muhammad Fuad Hasan'],
            ['emp_id' => 'I000336', 'full_name' => 'Dina Haryanti'],
            ['emp_id' => 'I000270', 'full_name' => 'Stevani Anastacia Manangka'],
            ['emp_id' => 'I000339', 'full_name' => 'Wendy Hardatama'],
            ['emp_id' => 'I000344', 'full_name' => 'Elga Rizky Pratama'],
            ['emp_id' => 'I000350', 'full_name' => 'Singkop Tatar Habeahan'],
            ['emp_id' => 'I000361', 'full_name' => 'Azzahra Putri Alwis'],
            ['emp_id' => 'I000357', 'full_name' => 'Andihika Dimartara'],
            ['emp_id' => 'I000360', 'full_name' => 'Ahmad Rifaldi'],
            ['emp_id' => 'I000362', 'full_name' => 'Herdhi Pratama Putra'],
            ['emp_id' => 'I000358', 'full_name' => 'Anhar Rasfati'],
            ['emp_id' => 'I000366', 'full_name' => 'Rinaldi Irwanto'],
            ['emp_id' => 'I000367', 'full_name' => 'Ibrahim'],
            ['emp_id' => 'I000369', 'full_name' => 'Roy Prasetio'],
            ['emp_id' => 'I000371', 'full_name' => 'Noor Arifin'],
            ['emp_id' => 'I000235', 'full_name' => 'Putri Dwi Juniarti'],
            ['emp_id' => 'I000354', 'full_name' => 'Ibnu Akbar'],
            ['emp_id' => 'I000372', 'full_name' => 'Swastika Adi Suranta T'],
            ['emp_id' => 'I000373', 'full_name' => 'Shaifur Rahman'],
            ['emp_id' => 'I000375', 'full_name' => 'Hengki Priando Halomoan'],
            ['emp_id' => 'I000378', 'full_name' => 'Ober Rezeki Purba'],
            ['emp_id' => 'I000380', 'full_name' => 'Adi Sudiyanto'],
            ['emp_id' => 'I000379', 'full_name' => 'Surya Pranoto'],
            ['emp_id' => 'I000382', 'full_name' => 'Muhammad Yusra Drei Nugrah'],
            ['emp_id' => 'I000385', 'full_name' => 'Bayu Arto Yudho'],
            ['emp_id' => 'I000386', 'full_name' => 'Natalia Puspa'],
            ['emp_id' => 'I000389', 'full_name' => 'Calvin Piter Unwawirka'],
            ['emp_id' => 'I000390', 'full_name' => 'Iyan Rahdian'],
            ['emp_id' => 'I000392', 'full_name' => 'Kristian Turnip'],
            ['emp_id' => 'I000394', 'full_name' => 'Mustofah'],
            ['emp_id' => 'I000395', 'full_name' => 'Muhammad Iqbal Fattah'],
            ['emp_id' => 'I000396', 'full_name' => 'Irwan Septihadi'],
            ['emp_id' => 'I000397', 'full_name' => 'Kukuh Widoyoko Utomo'],
            ['emp_id' => 'I000399', 'full_name' => 'Azzahra Putri Roslan'],
            ['emp_id' => 'I000400', 'full_name' => 'Dea Lita Putri Rifaz Sidik'],
            ['emp_id' => 'I000401', 'full_name' => 'Eka Arman Ferdianto'],
            ['emp_id' => 'I000402', 'full_name' => 'Redo Meidy Pratama'],
            ['emp_id' => 'I000403', 'full_name' => 'Angga Maulana Putra'],
            ['emp_id' => 'I000404', 'full_name' => 'Marina Ningsih'],
            ['emp_id' => 'I000409', 'full_name' => 'Ahmad Zulham Asaad'],
            ['emp_id' => 'I000407', 'full_name' => 'Heri Syahputra'],
            ['emp_id' => 'I000408', 'full_name' => 'Recky Danis'],
            ['emp_id' => 'I000412', 'full_name' => 'Jever Fidel Dharma Gea'],
            ['emp_id' => 'I000414', 'full_name' => 'Rico Pranata Sidauruk'],
            ['emp_id' => 'I000421', 'full_name' => 'Imbran Bachtiar Hidayat'],
            ['emp_id' => 'I000415', 'full_name' => 'Mochammad Yogi'],
            ['emp_id' => 'I000425', 'full_name' => 'Rudi Rusnandar'],
            ['emp_id' => 'I000416', 'full_name' => 'Rofiqoh Wulandari'],
            ['emp_id' => 'I000422', 'full_name' => 'Sumiarti, SE'],
            ['emp_id' => 'I000427', 'full_name' => 'Zahra Aliyyah Latief'],
            ['emp_id' => 'I000418', 'full_name' => 'Aditiaz Setiawan'],
            ['emp_id' => 'I000423', 'full_name' => 'Randy Ramadhani'],
            ['emp_id' => 'I000417', 'full_name' => 'Ady Candra'],
            ['emp_id' => 'I000426', 'full_name' => 'Farizan Adli Nugroho'],
            ['emp_id' => 'I000420', 'full_name' => 'KGS. M. Yunus Amir'],
            ['emp_id' => 'I000429', 'full_name' => 'Kevin Suryawan'],
            ['emp_id' => 'I000431', 'full_name' => 'Mochammad Trisnanto'],
            ['emp_id' => 'I000432', 'full_name' => 'Liberty Jonas Aruan'],
            ['emp_id' => 'I000433', 'full_name' => 'Alifiandi Hardiono'],
            ['emp_id' => 'I000434', 'full_name' => 'Rama Murtyza Erionadar'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['emp_id' => $user['emp_id']],
                [
                    'full_name'   => $user['full_name'],
                    'password'    => Hash::make($user['emp_id']),
                    'position_id' => 7,
                    'role_id'     => 4,
                    'sub_role'    => [4], // 👈 disimpan sebagai JSON string
                    'is_active'   => 1,
                    'is_deleted'  => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }
}
