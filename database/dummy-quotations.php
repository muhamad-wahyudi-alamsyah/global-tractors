<?php
/**
 * Dummy Data for Request Quotations
 * 
 * Usage: Run this script from WordPress admin or include in functions.php temporarily
 * 
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

function gti_insert_dummy_quotations() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'gti_quotations';
    
    // Check if data already exists
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    if ($count > 0) {
        return 'Data already exists. Skipping dummy data insertion.';
    }
    
    $dummy_data = [
        [
            'quotation_id' => 'RFQ-2405-0012',
            'customer_name' => 'Budi Santoso',
            'customer_company' => 'PT Abadi Sentosa',
            'customer_email' => 'budi@ptabadi.com',
            'customer_phone' => '+62 812-3456-7890',
            'customer_address' => 'Jl. Jend. Sudirman No. 52, Jakarta Selatan, DKI Jakarta 12190, Indonesia',
            'items' => json_encode([
                ['name' => 'Komatsu PC200-8', 'qty' => 2, 'unit_price' => 850000000, 'total' => 1700000000],
                ['name' => 'Hitachi Zaxis ZX200', 'qty' => 1, 'unit_price' => 780000000, 'total' => 780000000],
                ['name' => 'CAT 320D2 Hydraulic Excavator', 'qty' => 1, 'unit_price' => 920000000, 'total' => 920000000],
            ]),
            'subtotal' => 3400000000,
            'discount' => 5,
            'discount_type' => 'percentage',
            'tax_rate' => 11,
            'tax_amount' => 357390000,
            'total' => 3573900000,
            'sales_pic' => 'Andi Pratama',
            'valid_until' => '2024-06-14',
            'delivery_location' => 'Balikpapan, Kalimantan Timur',
            'additional_notes' => 'Mohon penawaran terbaik untuk kebutuhan project kami di site.',
            'status' => 'processing',
            'request_date' => '2024-05-31',
        ],
        [
            'quotation_id' => 'RFQ-2405-0013',
            'customer_name' => 'Siti Rahayu',
            'customer_company' => 'PT Makmur Sejahtera',
            'customer_email' => 'siti@makmursejahtera.co.id',
            'customer_phone' => '+62 811-8765-4321',
            'customer_address' => 'Jl. Gatot Subroto Kav. 45, Jakarta Selatan 12930, Indonesia',
            'items' => json_encode([
                ['name' => 'Volvo EC210D', 'qty' => 1, 'unit_price' => 895000000, 'total' => 895000000],
                ['name' => 'Bulldozer Komatsu D85', 'qty' => 2, 'unit_price' => 1250000000, 'total' => 2500000000],
            ]),
            'subtotal' => 3395000000,
            'discount' => 50000000,
            'discount_type' => 'amount',
            'tax_rate' => 11,
            'tax_amount' => 367995000,
            'total' => 3702995000,
            'sales_pic' => 'Rina Wulandari',
            'valid_until' => '2024-06-20',
            'delivery_location' => 'Surabaya, Jawa Timur',
            'additional_notes' => 'Butuh cepat untuk proyek infrastruktur.',
            'status' => 'new',
            'request_date' => '2024-06-01',
        ],
        [
            'quotation_id' => 'RFQ-2405-0014',
            'customer_name' => 'Ahmad Hidayat',
            'customer_company' => 'CV Karya Mandiri',
            'customer_email' => 'ahmad@karyamandiri.com',
            'customer_phone' => '+62 813-9988-7766',
            'customer_address' => 'Jl. Ahmad Yani No. 123, Bandung 40122, Jawa Barat, Indonesia',
            'items' => json_encode([
                ['name' => 'Wheel Loader SDLG LG936L', 'qty' => 3, 'unit_price' => 425000000, 'total' => 1275000000],
            ]),
            'subtotal' => 1275000000,
            'discount' => 0,
            'discount_type' => 'amount',
            'tax_rate' => 11,
            'tax_amount' => 140250000,
            'total' => 1415250000,
            'sales_pic' => 'Andi Pratama',
            'valid_until' => '2024-06-15',
            'delivery_location' => 'Bandung, Jawa Barat',
            'additional_notes' => 'Harga sudah termasuk ongkos kirim.',
            'status' => 'approved',
            'request_date' => '2024-05-28',
        ],
        [
            'quotation_id' => 'RFQ-2405-0015',
            'customer_name' => 'Dewi Kusuma',
            'customer_company' => 'PT Delta Construction',
            'customer_email' => 'dewi@deltaconstruction.com',
            'customer_phone' => '+62 817-2233-4455',
            'customer_address' => 'Jl. M.H. Thamrin No. 10, Jakarta Pusat 10350, Indonesia',
            'items' => json_encode([
                ['name' => 'Excavator Hyundai R220LC-9S', 'qty' => 2, 'unit_price' => 750000000, 'total' => 1500000000],
                ['name' => 'Motor Grader XCMG GR180', 'qty' => 1, 'unit_price' => 680000000, 'total' => 680000000],
                ['name' => 'Vibro Roller Dynapac CA150', 'qty' => 1, 'unit_price' => 520000000, 'total' => 520000000],
            ]),
            'subtotal' => 2700000000,
            'discount' => 3,
            'discount_type' => 'percentage',
            'tax_rate' => 11,
            'tax_amount' => 290619000,
            'total' => 2906190000,
            'sales_pic' => 'Bambang Sutrisno',
            'valid_until' => '2024-06-25',
            'delivery_location' => 'Medan, Sumatera Utara',
            'additional_notes' => 'Minta penawaran untuk rental dan beli.',
            'status' => 'waiting_customer',
            'request_date' => '2024-06-02',
        ],
        [
            'quotation_id' => 'RFQ-2405-0016',
            'customer_name' => 'Eko Prasetyo',
            'customer_company' => 'PT Nusantara Jaya',
            'customer_email' => 'eko@nusantarajaya.co.id',
            'customer_phone' => '+62 819-5544-3322',
            'customer_address' => 'Jl. Imam Bonjol No. 88, Denpasar 80113, Bali, Indonesia',
            'items' => json_encode([
                ['name' => 'Backhoe Loader JCB 3DX', 'qty' => 2, 'unit_price' => 580000000, 'total' => 1160000000],
            ]),
            'subtotal' => 1160000000,
            'discount' => 20000000,
            'discount_type' => 'amount',
            'tax_rate' => 11,
            'tax_amount' => 125580000,
            'total' => 1265580000,
            'sales_pic' => 'Rina Wulandari',
            'valid_until' => '2024-06-10',
            'delivery_location' => 'Denpasar, Bali',
            'additional_notes' => 'Butuh untuk proyek pembangunan hotel.',
            'status' => 'rejected',
            'request_date' => '2024-05-25',
        ],
        [
            'quotation_id' => 'RFQ-2405-0017',
            'customer_name' => 'Maya Indah',
            'customer_company' => 'PT Sinar Terang',
            'customer_email' => 'maya@sinarterang.com',
            'customer_phone' => '+62 815-6677-8899',
            'customer_address' => 'Jl. Pahlawan No. 45, Semarang 50145, Jawa Tengah, Indonesia',
            'items' => json_encode([
                ['name' => 'Dump Truck Mitsubishi Fuso', 'qty' => 5, 'unit_price' => 650000000, 'total' => 3250000000],
                ['name' => 'Concrete Mixer Truck Hino', 'qty' => 2, 'unit_price' => 890000000, 'total' => 1780000000],
            ]),
            'subtotal' => 5030000000,
            'discount' => 100000000,
            'discount_type' => 'amount',
            'tax_rate' => 11,
            'tax_amount' => 542330000,
            'total' => 5472330000,
            'sales_pic' => 'Bambang Sutrisno',
            'valid_until' => '2024-06-30',
            'delivery_location' => 'Semarang, Jawa Tengah',
            'additional_notes' => 'Pembayaran bisa dicicil 3x.',
            'status' => 'completed',
            'request_date' => '2024-05-20',
        ],
        [
            'quotation_id' => 'RFQ-2405-0018',
            'customer_name' => 'Fajar Nugroho',
            'customer_company' => 'CV Cahaya Baru',
            'customer_email' => 'fajar@cahayabaru.co.id',
            'customer_phone' => '+62 821-1122-3344',
            'customer_address' => 'Jl. Sudirman No. 77, Makassar 90115, Sulawesi Selatan, Indonesia',
            'items' => json_encode([
                ['name' => 'Excavator SANY SY215C', 'qty' => 1, 'unit_price' => 680000000, 'total' => 680000000],
            ]),
            'subtotal' => 680000000,
            'discount' => 0,
            'discount_type' => 'amount',
            'tax_rate' => 11,
            'tax_amount' => 74800000,
            'total' => 754800000,
            'sales_pic' => 'Andi Pratama',
            'valid_until' => '2024-06-18',
            'delivery_location' => 'Makassar, Sulawesi Selatan',
            'additional_notes' => 'Budget terbatas, mohon harga terbaik.',
            'status' => 'new',
            'request_date' => '2024-06-03',
        ],
        [
            'quotation_id' => 'RFQ-2405-0019',
            'customer_name' => 'Lestari Wijaya',
            'customer_company' => 'PT Graha Investama',
            'customer_email' => 'lestari@grahainvestama.com',
            'customer_phone' => '+62 823-4455-6677',
            'customer_address' => 'Jl. HR Rasuna Said Kav. C-10, Jakarta Selatan 12940, Indonesia',
            'items' => json_encode([
                ['name' => 'Crawler Crane XCMG XGC55', 'qty' => 1, 'unit_price' => 2800000000, 'total' => 2800000000],
                ['name' => 'Mobile Crane XCMG QY25K', 'qty' => 1, 'unit_price' => 1850000000, 'total' => 1850000000],
            ]),
            'subtotal' => 4650000000,
            'discount' => 7,
            'discount_type' => 'percentage',
            'tax_rate' => 11,
            'tax_amount' => 479115000,
            'total' => 4791150000,
            'sales_pic' => 'Rina Wulandari',
            'valid_until' => '2024-07-05',
            'delivery_location' => 'Jakarta, DKI Jakarta',
            'additional_notes' => 'Untuk proyek konstruksi gedung bertingkat.',
            'status' => 'processing',
            'request_date' => '2024-06-04',
        ],
    ];
    
    $inserted = 0;
    foreach ($dummy_data as $data) {
        $result = $wpdb->insert($table_name, $data);
        if ($result) {
            $inserted++;
        }
    }
    
    return "Successfully inserted {$inserted} dummy quotations.";
}

// Uncomment line below to run (add to functions.php or run directly)
// add_action('admin_init', 'gti_insert_dummy_quotations');
