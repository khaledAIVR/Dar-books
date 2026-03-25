<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SyncPlansArticleTwo extends Migration
{
    /**
     * Sync plans to Article 2 (المادة 2) four programs.
     * Does not delete plans; updates by id and inserts 4th plan if needed.
     *
     * @return void
     */
    public function up()
    {
        $plans = [
            [
                'id' => 2,
                'name' => 'رفيق الكتاب (خارجي)',
                'price' => 20,
                'books_quota' => 12,
                'note' => 'استعارة كتابين كل شهرين (٦ دفعات سنويًا – ١٢ كتابًا سنويًا). المبلغ السنوي يغطي تكاليف الشحن.',
            ],
            [
                'id' => 3,
                'name' => 'صديق الكتاب (خارجي)',
                'price' => 35,
                'books_quota' => 24,
                'note' => 'استعارة كتابين شهريًا (١٢ دفعة سنويًا – ٢٤ كتابًا سنويًا). المبلغ السنوي يغطي تكاليف الشحن.',
            ],
            [
                'id' => 4,
                'name' => 'الاستعارة العائلية (خارجي)',
                'price' => 0,
                'books_quota' => 0,
                'note' => 'يتم تحديد عدد الكتب ومرات الإرسال وفق عدد أفراد الأسرة وبالاتفاق مع إدارة المكتبة.',
            ],
            [
                'id' => 5,
                'name' => 'الاستعارة الداخلية (داخل مقر المكتبة)',
                'price' => 0,
                'books_quota' => 0,
                'note' => 'تأمين إلزامي ١٠ يورو للفرد بحد أقصى ٥٠ يورو للعائلة. يُعاد مبلغ التأمين عند إنهاء العضوية وبعد إعادة جميع الكتب المستعارة بحالة سليمة.',
            ],
        ];

        foreach ($plans as $plan) {
            $exists = DB::table('plans')->where('id', $plan['id'])->exists();
            $noteColumn = \Illuminate\Support\Facades\Schema::hasColumn('plans', 'note');

            if ($exists) {
                $update = [
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'books_quota' => $plan['books_quota'],
                    'updated_at' => now(),
                ];
                if ($noteColumn) {
                    $update['note'] = $plan['note'];
                }
                DB::table('plans')->where('id', $plan['id'])->update($update);
            } else {
                $insert = [
                    'id' => $plan['id'],
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'books_quota' => $plan['books_quota'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($noteColumn) {
                    $insert['note'] = $plan['note'];
                }
                DB::table('plans')->insert($insert);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Do not drop plans that may be referenced by subscriptions.
        // Optionally revert to previous names/values if you have backups.
    }
}
