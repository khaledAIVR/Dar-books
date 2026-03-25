<?php

return [
    /**
     * If true, the importer will ONLY accept categories from `allowed_categories`
     * (after aliases + Arabic normalization). Unknown values can fall back — see below.
     */
    'strict_categories' => true,

    /**
     * Used when the spreadsheet leaves Publisher empty.
     */
    'default_publisher' => 'غير محدد',

    /**
     * Used when Author is empty after column mapping.
     */
    'default_author' => 'غير محدد',

    /**
     * Used when the Category cell is empty (after trim).
     */
    'default_category' => 'أخرى',

    /**
     * Bucket for labels that are not close enough to any main category (see category_closest_min_score).
     * Must appear in allowed_categories.
     */
    'fallback_category_others' => 'أخرى',

    /**
     * Minimum similarity (0–1) to map an unknown label to the closest main category;
     * below this threshold the label is assigned to fallback_category_others.
     */
    'category_closest_min_score' => 0.28,

    /**
     * Canonical category names: the 17 main buckets + أخرى (Others).
     */
    'allowed_categories' => [
        'سير ومذكرات',
        'تاريخ وجغرافيا',
        'روايات',
        'فلسفة وفكر ومقالات',
        'علم نفس واجتماع',
        'فنون وشعر',
        'روايات مترجمة',
        'علوم طبية',
        'علوم سياسية وقانون',
        'أديان وعقائد',
        'لغات وقواميس',
        'علوم اللغة والترجمة',
        'إدارة واقتصاد',
        'تربية طفل',
        'أطفال وناشئة',
        'أدب ألماني',
        'اهداء رفيق شامي',
        'أخرى',
    ],

    /**
     * Map dataset / legacy labels => canonical name from allowed_categories.
     * Arabic hamza/alif variants are also unified in BookBulkImportService::normalizeCategoryName().
     */
    'category_aliases' => [
        // Legacy spellings without hamza (and other alif forms) → canonical
        'اديان وعقائد' => 'أديان وعقائد',
        'الأديان والعقائد' => 'أديان وعقائد',
        'الأديان و العقائد' => 'أديان وعقائد',
        'كتب دينية' => 'أديان وعقائد',

        'ادارة واقتصاد' => 'إدارة واقتصاد',
        'إقتصاد وإدارة أعمال' => 'إدارة واقتصاد',
        'الإقتصاد و الإدارة والتسويق' => 'إدارة واقتصاد',

        // Literature / novels
        'رواية' => 'روايات',
        'قصة' => 'روايات',
        'قصص' => 'روايات',
        'مجموعة قصصية' => 'روايات',
        'الأدب-قصص قصيرة' => 'روايات',
        'أدب' => 'روايات',
        'ادب' => 'روايات',

        // Translated literature
        'أدب مترجم' => 'روايات مترجمة',
        'مترجم' => 'روايات مترجمة',

        // Politics / law
        'سياسة' => 'علوم سياسية وقانون',
        'قانون' => 'علوم سياسية وقانون',
        'العلوم السياسية والعسكرية' => 'علوم سياسية وقانون',

        // History / geography / biographies
        'تاريخ' => 'تاريخ وجغرافيا',
        'جغرافيا' => 'تاريخ وجغرافيا',
        'تاريخ - سير وتراجم' => 'سير ومذكرات',
        'سير ذاتية' => 'سير ومذكرات',
        'سيرة' => 'سير ومذكرات',
        'سير' => 'سير ومذكرات',
        'مذكرات' => 'سير ومذكرات',

        // Philosophy / essays
        'فلسفة' => 'فلسفة وفكر ومقالات',
        'مقالات' => 'فلسفة وفكر ومقالات',
        'محاضره' => 'فلسفة وفكر ومقالات',
        'دراسه تحليليه' => 'فلسفة وفكر ومقالات',
        'دراسات إنسانية وفكرية' => 'فلسفة وفكر ومقالات',
        'فلسفة, فكر ومقالات' => 'فلسفة وفكر ومقالات',
        'فكر' => 'فلسفة وفكر ومقالات',

        // Language / translation / dictionaries
        'علم الترجمة' => 'علوم اللغة والترجمة',
        'مترجم \\ لغتين' => 'علوم اللغة والترجمة',
        'لغة' => 'علوم اللغة والترجمة',
        'ترجمة' => 'علوم اللغة والترجمة',
        'معاجم وقواميس' => 'لغات وقواميس',
        'مراجع وموسوعات' => 'لغات وقواميس',
        'موسوعات' => 'لغات وقواميس',
        'قاموس' => 'لغات وقواميس',
        'معجم' => 'لغات وقواميس',

        // Kids / parenting
        'أطفال' => 'أطفال وناشئة',
        'الأطفال' => 'أطفال وناشئة',
        'ناشئة' => 'أطفال وناشئة',
        'قصص أطفال' => 'أطفال وناشئة',
        'قصص أطفال أجنبية' => 'أطفال وناشئة',
        'تربية أطفال' => 'تربية طفل',
        'تربية' => 'تربية طفل',

        // Arts / poetry / theater
        'فنون' => 'فنون وشعر',
        'شعر' => 'فنون وشعر',
        'قصائد و نصوص' => 'فنون وشعر',
        'مسرح' => 'فنون وشعر',
        'مسرحية' => 'فنون وشعر',

        // Medical / science
        'علوم بحتة مع الطب' => 'علوم طبية',
        'العلوم والطبيعة' => 'علوم طبية',
        'صحة عامة ونظام غذائى' => 'علوم طبية',
        'الطبخ' => 'علوم طبية',
        'طب' => 'علوم طبية',

        // Psychology / social
        'المرأة والعائلة' => 'علم نفس واجتماع',
        'تنمية ذاتية وهوايات' => 'علم نفس واجتماع',
        'نفس' => 'علم نفس واجتماع',
        'اجتماع' => 'علم نفس واجتماع',

        // German lit / dedication — short forms
        'ألماني' => 'أدب ألماني',
        'الماني' => 'أدب ألماني',
        'رفيق شامي' => 'اهداء رفيق شامي',
        'اهداء' => 'اهداء رفيق شامي',

        // Short religion / admin (closest-match also handles "اديان" substring → أديان وعقائد)
        'اديان' => 'أديان وعقائد',
        'دين' => 'أديان وعقائد',
        'عقائد' => 'أديان وعقائد',

        // Uncategorized / placeholders → Others
        'غير مصنف' => 'أخرى',
        'Category 1' => 'أخرى',
        'Category 2' => 'أخرى',
        'category' => 'أخرى',
    ],
];
