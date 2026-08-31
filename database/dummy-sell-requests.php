<?php
/**
 * Dummy Data for Sell Equipment Requests
 * 
 * Usage: Run this script from WordPress admin or include in functions.php temporarily
 * 
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

function gti_insert_dummy_sell_requests() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gti_sell_requests';
    
    // Check if data already exists
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    if ($count > 0) {
        return 'Data already exists. Skipping dummy data insertion.';
    }
    
    $dummy_data = [
        [
            'customer_name' => 'Budi Santoso',
            'customer_company' => 'PT Abadi Sentosa',
            'customer_email' => 'budi@ptabadi.com',
            'customer_phone' => '+62 812-3456-7890',
            'equipment_name' => 'Komatsu PC200-8',
            'equipment_brand' => 'Komatsu',
            'equipment_model' => 'PC200-8',
            'equipment_year' => 2020,
            'equipment_condition' => 'excellent',
            'equipment_hours' => 3200,
            'offered_price' => 750000000,
            'images' => '[]',
            'status' => 'new',
        ],
        [
            'customer_name' => 'Siti Rahayu',
            'customer_company' => 'PT Makmur Sejahtera',
            'customer_email' => 'siti@makmursejahtera.co.id',
            'customer_phone' => '+62 811-8765-4321',
            'equipment_name' => 'Hitachi Zaxis ZX200',
            'equipment_brand' => 'Hitachi',
            'equipment_model' => 'ZX200',
            'equipment_year' => 2019,
            'equipment_condition' => 'good',
            'equipment_hours' => 5100,
            'offered_price' => 620000000,
            'images' => '[]',
            'status' => 'processing',
        ],
        [
            'customer_name' => 'Ahmad Hidayat',
            'customer_company' => 'CV Karya Mandiri',
            'customer_email' => 'ahmad@karyamandiri.com',
            'customer_phone' => '+62 813-9988-7766',
            'equipment_name' => 'CAT 320D2',
            'equipment_brand' => 'CAT',
            'equipment_model' => '320D2',
            'equipment_year' => 2018,
            'equipment_condition' => 'good',
            'equipment_hours' => 6800,
            'offered_price' => 680000000,
            'images' => '[]',
            'status' => 'approved',
        ],
        [
            'customer_name' => 'Dewi Kusuma',
            'customer_company' => 'PT Delta Construction',
            'customer_email' => 'dewi@deltaconstruction.com',
            'customer_phone' => '+62 817-2233-4455',
            'equipment_name' => 'Volvo EC210D',
            'equipment_brand' => 'Volvo',
            'equipment_model' => 'EC210D',
            'equipment_year' => 2021,
            'equipment_condition' => 'excellent',
            'equipment_hours' => 2100,
            'offered_price' => 820000000,
            'images' => '[]',
            'status' => 'new',
        ],
        [
            'customer_name' => 'Eko Prasetyo',
            'customer_company' => 'PT Nusantara Jaya',
            'customer_email' => 'eko@nusantarajaya.co.id',
            'customer_phone' => '+62 819-5544-3322',
            'equipment_name' => 'Komatsu D65PX-18',
            'equipment_brand' => 'Komatsu',
            'equipment_model' => 'D65PX-18',
            'equipment_year' => 2017,
            'equipment_condition' => 'fair',
            'equipment_hours' => 8900,
            'offered_price' => 950000000,
            'images' => '[]',
            'status' => 'rejected',
        ],
        [
            'customer_name' => 'Maya Indah',
            'customer_company' => 'PT Sinar Terang',
            'customer_email' => 'maya@sinarterang.com',
            'customer_phone' => '+62 815-6677-8899',
            'equipment_name' => 'SDLG LG936L',
            'equipment_brand' => 'SDLG',
            'equipment_model' => 'LG936L',
            'equipment_year' => 2022,
            'equipment_condition' => 'excellent',
            'equipment_hours' => 1500,
            'offered_price' => 380000000,
            'images' => '[]',
            'status' => 'completed',
        ],
        [
            'customer_name' => 'Fajar Nugroho',
            'customer_company' => 'CV Cahaya Baru',
            'customer_email' => 'fajar@cahayabaru.co.id',
            'customer_phone' => '+62 821-1122-3344',
            'equipment_name' => 'SANY SY215C',
            'equipment_brand' => 'SANY',
            'equipment_model' => 'SY215C',
            'equipment_year' => 2020,
            'equipment_condition' => 'good',
            'equipment_hours' => 4200,
            'offered_price' => 550000000,
            'images' => '[]',
            'status' => 'processing',
        ],
        [
            'customer_name' => 'Lestari Wijaya',
            'customer_company' => 'PT Graha Investama',
            'customer_email' => 'lestari@grahainvestama.com',
            'customer_phone' => '+62 823-4455-6677',
            'equipment_name' => 'Hyundai R220LC-9S',
            'equipment_brand' => 'Hyundai',
            'equipment_model' => 'R220LC-9S',
            'equipment_year' => 2019,
            'equipment_condition' => 'good',
            'equipment_hours' => 5800,
            'offered_price' => 620000000,
            'images' => '[]',
            'status' => 'new',
        ],
        [
            'customer_name' => 'Rizky Pratama',
            'customer_company' => 'PT Berkah Jaya',
            'customer_email' => 'rizky@berkahjaya.co.id',
            'customer_phone' => '+62 816-7788-9900',
            'equipment_name' => 'XCMG GR180',
            'equipment_brand' => 'XCMG',
            'equipment_model' => 'GR180',
            'equipment_year' => 2021,
            'equipment_condition' => 'excellent',
            'equipment_hours' => 1800,
            'offered_price' => 580000000,
            'images' => '[]',
            'status' => 'approved',
        ],
        [
            'customer_name' => 'Putri Anjani',
            'customer_company' => 'CV Mitra Konstruksi',
            'customer_email' => 'putri@mitrakonstruksi.com',
            'customer_phone' => '+62 822-3344-5566',
            'equipment_name' => 'JCB 3DX Backhoe',
            'equipment_brand' => 'JCB',
            'equipment_model' => '3DX',
            'equipment_year' => 2018,
            'equipment_condition' => 'fair',
            'equipment_hours' => 7200,
            'offered_price' => 420000000,
            'images' => '[]',
            'status' => 'new',
        ],
        [
            'customer_name' => 'Hendra Wijaya',
            'customer_company' => 'PT Cipta Bangun',
            'customer_email' => 'hendra@ciptabangun.co.id',
            'customer_phone' => '+62 818-2233-4455',
            'equipment_name' => 'Caterpillar D6T',
            'equipment_brand' => 'CAT',
            'equipment_model' => 'D6T',
            'equipment_year' => 2016,
            'equipment_condition' => 'poor',
            'equipment_hours' => 11200,
            'offered_price' => 780000000,
            'images' => '[]',
            'status' => 'processing',
        ],
        [
            'customer_name' => 'Anisa Permata',
            'customer_company' => 'PT Sukses Mandiri',
            'customer_email' => 'anisa@suksesmandiri.com',
            'customer_phone' => '+62 857-1122-3344',
            'equipment_name' => 'Doosan DX225LC',
            'equipment_brand' => 'Doosan',
            'equipment_model' => 'DX225LC',
            'equipment_year' => 2020,
            'equipment_condition' => 'good',
            'equipment_hours' => 3900,
            'offered_price' => 590000000,
            'images' => '[]',
            'status' => 'completed',
        ],
        [
            'customer_name' => 'Tony Saputra',
            'customer_company' => 'CV Jaya Abadi',
            'customer_email' => 'tony@jayaabadi.co.id',
            'customer_phone' => '+62 878-5566-7788',
            'equipment_name' => 'Kobelco SK200-10',
            'equipment_brand' => 'Kobelco',
            'equipment_model' => 'SK200-10',
            'equipment_year' => 2022,
            'equipment_condition' => 'excellent',
            'equipment_hours' => 1200,
            'offered_price' => 720000000,
            'images' => '[]',
            'status' => 'new',
        ],
        [
            'customer_name' => 'Ratna Sari',
            'customer_company' => 'PT Multi Teknik',
            'customer_email' => 'ratna@multiteknik.co.id',
            'customer_phone' => '+62 812-9988-7766',
            'equipment_name' => 'Liebherr R 920',
            'equipment_brand' => 'Liebherr',
            'equipment_model' => 'R 920',
            'equipment_year' => 2019,
            'equipment_condition' => 'good',
            'equipment_hours' => 4600,
            'offered_price' => 850000000,
            'images' => '[]',
            'status' => 'rejected',
        ],
        [
            'customer_name' => 'Dimas Aditya',
            'customer_company' => 'CV Bintang Mas',
            'customer_email' => 'dimas@bintangmas.com',
            'customer_phone' => '+62 856-3344-5566',
            'equipment_name' => 'Yangmei YSMC820',
            'equipment_brand' => 'Yangmei',
            'equipment_model' => 'YSMC820',
            'equipment_year' => 2021,
            'equipment_condition' => 'good',
            'equipment_hours' => 2800,
            'offered_price' => 350000000,
            'images' => '[]',
            'status' => 'approved',
        ],
    ];
    
    $inserted = 0;
    
    foreach ($dummy_data as $data) {
        $data['created_at'] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'));
        $data['updated_at'] = $data['created_at'];
        
        $result = $wpdb->insert($table_name, $data);
        if ($result) {
            $inserted++;
        }
    }
    
    return "Successfully inserted {$inserted} sell equipment dummy records.";
}

// Auto-run if accessed directly
if (php_sapi_name() === 'cli' || (isset($_GET['run_dummy']) && $_GET['run_dummy'] === 'sell')) {
    add_action('init', function() {
        echo gti_insert_dummy_sell_requests();
    });
}
