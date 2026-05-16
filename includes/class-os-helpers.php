<?php
/**
 * Olama Stores Helpers Class
 * Handles custom translation mapping and shared utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

class OS_Helpers
{

    /**
     * Check if the system is in Arabic mode based on school settings
     */
    public static function is_arabic()
    {
        $settings = get_option('olama_school_settings', array());
        return isset($settings['default_lang']) && $settings['default_lang'] === 'ar';
    }

    /**
     * Translate strings based on a static map
     * 
     * @param string $text The English text to translate
     * @return string Translated text if in Arabic mode and map exists, otherwise original
     */
    public static function translate($text)
    {
        if ($text === null) {
            return '';
        }

        $text = trim($text);

        if (!self::is_arabic()) {
            return $text;
        }

        static $map = array(
        // Fabrics, Colors, Sizes
        'Copy' => 'نسخة',
        'Duplicate' => 'نسخ',
        'Fabrics' => 'الأقمشة',
        'Fabric Name' => 'اسم القماش',
        'Add Fabric' => 'إضافة قماش',
        'Select Fabric' => 'اختر القماش',
        'Fabric' => 'القماش',
        'Colors' => 'الألوان',
        'Color Name' => 'اسم اللون',
        'Add Color' => 'إضافة لون',
        'Select Color' => 'اختر اللون',
        'Color' => 'اللون',
        'Sizes' => 'المقاسات',
        'Size Name/Number' => 'اسم/رقم المقاس',
        'Add Size' => 'إضافة مقاس',
        'Select Size' => 'اختر المقاس',
        'Size' => 'المقاس',
        'Manage colors that can be assigned to school items.' => 'إدارة الألوان التي يمكن تخصيصها لمواد المدرسة.',
        'Manage sizes that can be assigned to school items.' => 'إدارة المقاسات التي يمكن تخصيصها لمواد المدرسة.',

        // Sidebar Menus & Core
        'Olama Stores' => 'المخزون',
        'Dashboard' => 'لوحة القيادة',
        'Item Registry' => 'سجل المواد',
        'Add Items' => 'إضافة مواد',
        'Stock' => 'المخزون',
        'Employee Custody' => 'عُهد الموظفين',
        'Student Withdrawals' => 'سحوبات الطلاب',
        'Reports' => 'التقارير',
        'Order Estimation' => 'تقدير الطلبات',
        'Settings' => 'الإعدادات',

        // Student Size Registration Tab
        'ACADEMIC YEAR' => 'العام الدراسي',
        'SEMESTER' => 'الفصل الدراسي',
        'Active' => 'نشط',
        'GRADE' => 'الصف',
        'SECTION' => 'الشعبة',
        'SHOW' => 'عرض',
        'All Students' => 'جميع الطلاب',
        'Load Students' => 'تحميل الطلاب',
        'TOTAL STUDENTS' => 'إجمالي الطلاب',
        'SIZED' => 'تم أخذ المقاس',
        'REMAINING' => 'المتبقي',
        'COMPLETION' => 'نسبة الإنجاز',
        'Print Empty Form' => 'طباعة نموذج فارغ',
        'Size Distribution (Actual)' => 'توزيع المقاسات (الفعلي)',
        'TOTAL' => 'الإجمالي',

        // Tabs & General
        'Input & Estimate' => 'الإدخال والتقدير',
        'Student Size Registration' => 'تسجيل مقاسات الطلاب',
        'Distribution Config' => 'إعدادات التوزيع',
        'Results & Charts' => 'النتائج والرسوم البيانية',
        'Supplier Summary' => 'طلبية المورد',
        'Saved Drafts' => 'المسودات المحفوظة',
        'Custom Order Estimation' => 'تقدير الطلبات المخصصة',

        // Tab 1: Input & Estimate
        'Expected Students Per Grade' => 'الطلاب المتوقعون لكل صف',
        'KG1' => 'الروضة الأولى',
        'KG2' => 'الروضة الثانية',
        'Grade 1' => 'الصف الأول',
        'Grade 2' => 'الصف الثاني',
        'Grade 3' => 'الصف الثالث',
        'Grade 4' => 'الصف الرابع',
        'Grade 5' => 'الصف الخامس',
        'Grade 6' => 'الصف السادس',
        'Grade 7' => 'الصف السابع',
        'Grade 8' => 'الصف الثامن',
        'Grade 9' => 'الصف التاسع',
        'G10/G11/G12' => 'العاشر/الأول ثانوي/التوجيهي',
        'Estimation Options' => 'خيارات التقدير',
        'Safety Margin Buffer' => 'هامش الأمان',
        'Custom' => 'مخصص',
        'Custom %:' => 'نسبة مخصصة:',
        'Adds a buffer on top of calculated quantities to cover unexpected demand.' => 'يضيف هامشاً إضافياً فوق الكميات المحسوبة لتغطية أي طلب غير متوقع.',
        'Manual Adjustment Mode' => 'وضع التعديل اليدوي',
        'Allows overriding calculated quantities in the results table before export.' => 'يسمح بتعديل الكميات المحسوبة في جدول النتائج قبل التصدير.',
        'Draft Name (for saving)' => 'اسم المسودة (للحفظ)',
        'e.g. 2025-2026 Estimate' => 'مثال: تقدير 2025-2026',
        'Calculate Estimation' => 'حساب التقدير',
        'Save Draft' => 'حفظ كمسودة',
        'Reset' => 'إعادة تعيين',
        'Per-Grade Size Estimation' => 'تقدير المقاسات لكل صف',

        // Tab 2: Distribution Config
        'Size Distribution Per Grade' => 'توزيع المقاسات لكل صف',
        'Reset All to Defaults' => 'إعادة تعيين للوضع الافتراضي',
        'Save Distribution' => 'حفظ التوزيع',
        'Adjust the size distribution percentages for each grade. Each grade\'s percentages must total 100%. You can add or remove sizes per grade.' => 'قم بتعديل نسب توزيع المقاسات لكل صف. يجب أن يكون المجموع 100%. يمكنك إضافة أو إزالة مقاسات لكل صف.',

        // Tab 3: Results & Charts
        'Please enter grade counts and click "Calculate Estimation" first.' => 'يرجى إدخال أعداد الطلاب ثم النقر على "حساب التقدير" أولاً.',
        'Grand Total — All Grades Combined' => 'المجموع الكلي — لجميع الصفوف',
        'Print' => 'طباعة',
        'Export CSV' => 'تصدير إلى CSV',
        'Export Excel' => 'تصدير إلى إكسل',
        'Uniform Items Breakdown' => 'تفصيل مواد الزي',
        'Size Distribution (Pie)' => 'توزيع المقاسات (دائري)',
        'Grade vs. Sizes (Bar)' => 'الصفوف والمقاسات (أعمدة)',

        // Supplier Summary Headers & Terms
        'Supplier Purchase Summary' => 'ملخص مشتريات المورد',
        'Unit Cost' => 'تكلفة الوحدة',
        'Total Cost' => 'التكلفة الإجمالية',
        'Price Status' => 'حالة السعر',
        'Missing' => 'مفقود',
        'OK' => 'مكتمل',
        'GRAND TOTAL' => 'المجموع الكلي',
        'Size' => 'المقاس',
        'Item' => 'مادة',
        'Qty' => 'الكمية',
        'Polo' => 'بولو',
        'Hoody' => 'هودي',
        'Pants' => 'بنطلون',
        'Total Units' => 'إجمالي الوحدات',
        'Supplier' => 'المورد',
        'Supplier Report Type' => 'نوع تقرير المورد',
        'Generate Report' => 'إنشاء التقرير',
        'Actual Scanned Qty' => 'الكمية الممسوحة فعلياً',

        // Report Types
        'Estimation Purchase Report' => 'تقرير مشتريات التقدير',
        'Actual Scan Purchase Report' => 'تقرير مشتريات المسح الفعلي',
        'Estimation → Inventory Report' => 'التقدير ← تقرير المخزون',
        'Actual Scan → Inventory Report' => 'المسح الفعلي ← تقرير المخزون',
        'Estimation Purchase with Cost' => 'مشتريات التقدير مع التكلفة',
        'Actual Scan with Cost' => 'المسح الفعلي مع التكلفة',
        'Estimation → Inventory with Cost' => 'التقدير ← المخزون مع التكلفة',
        'Actual Scan → Inventory with Cost' => 'المسح الفعلي ← المخزون مع التكلفة',

        // Messages
        'Please calculate an estimation first.' => 'يرجى حساب التقدير أولاً.',
        'Requires the Olama School System plugin to be active.' => 'يتطلب تفعيل إضافة نظام مدرسة علماء.',
        'Olama Stores requires Olama School System %s or higher.' => 'يتطلب متجر علماء نظام مدرسة علماء %s أو أعلى.',
        );

        return isset($map[$text]) ? $map[$text] : $text;
    }
}
