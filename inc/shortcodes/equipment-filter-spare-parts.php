<?php
/**
 * [gti_spare_parts_filter] Shortcode — Spare Parts Catalog with Filter Panel
 *
 * Usage:
 *   [gti_spare_parts_filter]
 *   [gti_spare_parts_filter per_page="12"]
 *
 * Queries gti_spare_parts table.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'gti_spare_parts_filter', 'gti_render_spare_parts_filter' );

function gti_render_spare_parts_filter( $atts = [] ) {
    $defaults = [
        'per_page' => 12,
    ];

    $atts = shortcode_atts( $defaults, $atts, 'gti_spare_parts_filter' );
    $per_page = absint( $atts['per_page'] );

    // Detail Page Mode
    $part_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( $part_id > 0 ) {
        return gti_spare_render_part_detail( $part_id );
    }

    // Get spare parts data
    $parts_data  = gti_get_spare_parts_data();
    $categories  = gti_get_spare_parts_categories( $parts_data );
    $brands      = gti_get_spare_parts_brands( $parts_data );
    $suppliers   = gti_get_spare_parts_suppliers( $parts_data );
    $locations   = gti_get_spare_parts_locations( $parts_data );

    ob_start();
    ?>
    <div class="gti-ef-wrapper gti-sp-wrapper" id="gti-spare-parts-filter"
         data-type="spare_parts"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'gti_spare_parts_filter' ) ); ?>"
         data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

        <!-- Mobile Filter Toggle -->
        <button class="gti-ef-mobile-filter-toggle" id="gti-ef-mobile-toggle" type="button">
            <i class="fas fa-sliders-h"></i>
            <span>Filters</span>
        </button>

        <!-- Mobile Overlay -->
        <div class="gti-ef-mobile-overlay" id="gti-ef-mobile-overlay"></div>

        <div class="gti-ef-container">
            <!-- ═══ SIDEBAR FILTER PANEL ═══ -->
            <aside class="gti-ef-sidebar" id="gti-ef-sidebar">
                <div class="gti-ef-filters">

                    <!-- CATEGORIES -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header open" data-filter="category">
                            <h4>CATEGORIES</h4>
                            <span class="toggle-icon"><i class="fas fa-minus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body open" data-filter-body="category">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $categories as $cat ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-category" value="<?php echo esc_attr( $cat['slug'] ); ?>">
                                        <span><?php echo esc_html( $cat['name'] ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BRAND -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="brand">
                            <h4>BRAND</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="brand">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $brands as $brand ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-brand" value="<?php echo esc_attr( $brand ); ?>">
                                        <span><?php echo esc_html( $brand ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SUPPLIER -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="supplier">
                            <h4>SUPPLIER</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="supplier">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $suppliers as $sup ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-supplier" value="<?php echo esc_attr( $sup ); ?>">
                                        <span><?php echo esc_html( $sup ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PRICE RANGE -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="price">
                            <h4>PRICE RANGE</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="price">
                            <div class="gti-ef-range-inputs">
                                <input type="text" name="ef-price-min" placeholder="Min (Rp)">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="text" name="ef-price-max" placeholder="Max (Rp)">
                            </div>
                        </div>
                    </div>

                    <!-- STOCK STATUS -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="stock">
                            <h4>STOCK STATUS</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="stock">
                            <div class="gti-ef-checkbox-list">
                                <label class="gti-ef-checkbox-item">
                                    <input type="checkbox" name="ef-stock" value="in_stock">
                                    <span>In Stock</span>
                                </label>
                                <label class="gti-ef-checkbox-item">
                                    <input type="checkbox" name="ef-stock" value="low_stock">
                                    <span>Low Stock</span>
                                </label>
                                <label class="gti-ef-checkbox-item">
                                    <input type="checkbox" name="ef-stock" value="out_of_stock">
                                    <span>Out of Stock</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- LOCATION -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="location">
                            <h4>LOCATION</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="location">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $locations as $loc ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-location" value="<?php echo esc_attr( $loc ); ?>">
                                        <span><?php echo esc_html( $loc ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="gti-ef-actions">
                    <button type="button" class="gti-ef-btn-reset" id="gti-ef-reset">
                        <i class="fas fa-redo-alt"></i>
                        <span>RESET FILTER</span>
                    </button>
                    <button type="button" class="gti-ef-btn-apply" id="gti-ef-apply">
                        <i class="fas fa-filter"></i>
                        <span>APPLY FILTER</span>
                    </button>
                </div>
            </aside>

            <!-- ═══ MAIN CONTENT ═══ -->
            <div class="gti-ef-content">
                <!-- Top Bar -->
                <div class="gti-ef-topbar">
                    <div class="gti-ef-result-count" id="gti-ef-result-count">
                        Showing <strong>1 - <?php echo esc_html( $per_page ); ?></strong> of <strong><?php echo esc_html( count( $parts_data ) ); ?></strong> items
                    </div>
                    <div class="gti-ef-topbar-right">
                        <div class="gti-ef-sort-wrapper">
                            <span class="gti-ef-sort-label">Sort by:</span>
                            <select class="gti-ef-sort-select" id="gti-ef-sort">
                                <option value="newest">Newest First</option>
                                <option value="name-asc">Name: A to Z</option>
                                <option value="name-desc">Name: Z to A</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                            </select>
                        </div>
                        <div class="gti-ef-view-switcher">
                            <button class="gti-ef-view-btn active" data-view="grid" title="Grid View"><i class="fas fa-th"></i></button>
                            <button class="gti-ef-view-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Card Grid -->
                <div class="gti-ef-grid" id="gti-ef-grid">
                    <?php foreach ( array_slice( $parts_data, 0, $per_page ) as $item ) : ?>
                        <?php gti_spare_render_part_card( $item ); ?>
                    <?php endforeach; ?>
                </div>

                <!-- Loading Skeleton -->
                <div class="gti-ef-loading" id="gti-ef-loading">
                    <div class="gti-ef-grid">
                        <?php for ( $i = 0; $i < 6; $i++ ) : ?>
                            <div class="gti-ef-skeleton-card">
                                <div class="gti-ef-skeleton-image"></div>
                                <div class="gti-ef-skeleton-body">
                                    <div class="gti-ef-skeleton-line" style="width:80%"></div>
                                    <div class="gti-ef-skeleton-line" style="width:60%"></div>
                                    <div class="gti-ef-skeleton-line" style="width:100%"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- No Results -->
                <div class="gti-ef-no-results" id="gti-ef-no-results" style="display:none;">
                    <i class="fas fa-search"></i>
                    <h3>No spare parts found</h3>
                    <p>Try adjusting your filters or search criteria to find what you're looking for.</p>
                </div>

                <!-- Pagination -->
                <div class="gti-ef-pagination" id="gti-ef-pagination">
                    <?php gti_spare_render_pagination( count( $parts_data ), $per_page ); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// ═══════════════════════════════════════════════════════════════════════════
// CARD, PAGINATION, DATA, FILTER HELPERS
// ═══════════════════════════════════════════════════════════════════════════

function gti_spare_render_part_card( $item ) {
    $title       = isset( $item['name'] ) ? $item['name'] : 'Spare Part';
    $part_number = isset( $item['part_number'] ) ? $item['part_number'] : '';
    $brand       = isset( $item['brand'] ) ? $item['brand'] : '';
    $category    = isset( $item['category'] ) ? $item['category'] : '';
    $price       = isset( $item['unit_price'] ) ? $item['unit_price'] : '';
    $stock       = isset( $item['stock'] ) ? (int) $item['stock'] : 0;
    $min_stock   = isset( $item['minimum_stock'] ) ? (int) $item['minimum_stock'] : 10;
    $location    = isset( $item['location'] ) ? $item['location'] : '—';
    $image_url   = isset( $item['image'] ) ? $item['image'] : '';
    $id          = isset( $item['id'] ) ? $item['id'] : 0;
    $status      = isset( $item['status'] ) ? $item['status'] : 'in_stock';

    // Stock status badge
    if ( $stock <= 0 ) {
        $stock_class = 'out-of-stock';
        $stock_label = 'OUT OF STOCK';
    } elseif ( $stock <= $min_stock ) {
        $stock_class = 'low-stock';
        $stock_label = 'LOW STOCK (' . $stock . ')';
    } else {
        $stock_class = 'in-stock';
        $stock_label = 'IN STOCK (' . $stock . ')';
    }
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'id', $id ) ); ?>" class="gti-ef-card gti-sp-card"
         data-id="<?php echo esc_attr( $id ); ?>"
         data-category="<?php echo esc_attr( $category ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-price="<?php echo esc_attr( $price ); ?>"
         data-stock="<?php echo esc_attr( $stock ); ?>"
         data-location="<?php echo esc_attr( $location ); ?>">
        <div class="gti-ef-card-image">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="placeholder-icon"><i class="fas fa-cogs"></i></div>
            <?php endif; ?>
            <span class="gti-ef-status-badge <?php echo esc_attr( $stock_class ); ?>"><?php echo esc_html( $stock_label ); ?></span>
        </div>
        <div class="gti-ef-card-body">
            <h3 class="gti-ef-card-title"><?php echo esc_html( $title ); ?></h3>
            <?php if ( $part_number ) : ?>
                <div class="gti-ef-card-part-number"><strong>Part #:</strong> <?php echo esc_html( $part_number ); ?></div>
            <?php endif; ?>
            <div class="gti-ef-card-meta">
                <?php if ( $brand ) : ?>
                    <div class="gti-ef-card-meta-row"><i class="fas fa-industry"></i><span><?php echo esc_html( $brand ); ?></span></div>
                <?php endif; ?>
                <div class="gti-ef-card-meta-row"><i class="fas fa-tag"></i><span><?php echo esc_html( $category ); ?></span></div>
                <div class="gti-ef-card-meta-row"><i class="fas fa-map-marker-alt"></i><span><?php echo esc_html( $location ); ?></span></div>
            </div>
            <?php if ( $price ) : ?>
                <div class="gti-ef-card-price">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?></div>
            <?php endif; ?>
            <span class="gti-ef-card-cta">REQUEST QUOTATION <i class="fas fa-arrow-right"></i></span>
        </div>
    </a>
    <?php
}

function gti_spare_render_pagination( $total, $per_page ) {
    $total_pages = max( 1, ceil( $total / $per_page ) );
    $current     = 1;

    echo '<button class="gti-ef-page-btn" data-page="prev" disabled><i class="fas fa-chevron-left"></i></button>';

    $max_visible = 5;
    $start = max( 1, $current - floor( $max_visible / 2 ) );
    $end   = min( $total_pages, $start + $max_visible - 1 );
    if ( $end - $start < $max_visible - 1 ) {
        $start = max( 1, $end - $max_visible + 1 );
    }

    if ( $start > 1 ) {
        echo '<button class="gti-ef-page-btn" data-page="1">1</button>';
        if ( $start > 2 ) {
            echo '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
    }

    for ( $i = $start; $i <= $end; $i++ ) {
        $active = $i === $current ? ' active' : '';
        echo '<button class="gti-ef-page-btn' . $active . '" data-page="' . $i . '">' . $i . '</button>';
    }

    if ( $end < $total_pages ) {
        if ( $end < $total_pages - 1 ) {
            echo '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
        echo '<button class="gti-ef-page-btn" data-page="' . $total_pages . '">' . $total_pages . '</button>';
    }

    $next_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="next"' . $next_disabled . '><i class="fas fa-chevron-right"></i></button>';

    $last_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="last"' . $last_disabled . '><i class="fas fa-angle-double-right"></i></button>';
}

// ═══════════════════════════════════════════════════════════════════════════
// DATA FUNCTIONS — spare parts
// ═══════════════════════════════════════════════════════════════════════════

function gti_get_spare_parts_data() {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_spare_parts';

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $table_exists ) {
        return [];
    }

    $rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );

    if ( empty( $rows ) ) {
        return gti_get_dummy_spare_parts_data();
    }

    $items = [];
    foreach ( $rows as $row ) {
        $stock     = (int) $row->stock;
        $min_stock = (int) $row->minimum_stock;

        if ( $stock <= 0 ) {
            $display_status = 'out_of_stock';
        } elseif ( $stock <= $min_stock ) {
            $display_status = 'low_stock';
        } else {
            $display_status = 'in_stock';
        }

        $items[] = [
            'id'            => (int) $row->id,
            'name'          => $row->name ?: 'Spare Part',
            'part_number'   => $row->part_number ?: '',
            'category'      => strtolower( $row->category ?: 'others' ),
            'brand'         => $row->brand ?: '',
            'description'   => $row->description ?: '',
            'stock'         => $stock,
            'minimum_stock' => $min_stock,
            'unit_price'    => $row->unit_price ?: '',
            'supplier'      => $row->supplier ?: '',
            'location'      => $row->location ?: '',
            'image'         => $row->image ?: '',
            'status'        => $display_status,
        ];
    }

    return $items;
}

function gti_get_dummy_spare_parts_data() {
    // Spare parts dummy data (same as dashboard template)
    return [
        [
            'id' => 1, 'name' => 'Oil Filter Komatsu', 'part_number' => 'KO-OF-PC200',
            'category' => 'engine-parts', 'brand' => 'KOMATSU', 'stock' => 25,
            'minimum_stock' => 10, 'unit_price' => 350000, 'supplier' => 'Komatsu Parts Center',
            'location' => 'Balikpapan', 'image' => '', 'status' => 'in_stock',
        ],
        [
            'id' => 2, 'name' => 'Hydraulic Filter CAT', 'part_number' => 'CAT-HF-320',
            'category' => 'hydraulic-parts', 'brand' => 'CATERPILLAR', 'stock' => 8,
            'minimum_stock' => 10, 'unit_price' => 450000, 'supplier' => 'CAT Parts Indonesia',
            'location' => 'Samarinda', 'image' => '', 'status' => 'low_stock',
        ],
        [
            'id' => 3, 'name' => 'Air Filter Hitachi', 'part_number' => 'HI-AF-ZX200',
            'category' => 'engine-parts', 'brand' => 'HITACHI', 'stock' => 15,
            'minimum_stock' => 10, 'unit_price' => 280000, 'supplier' => 'Hitachi Parts',
            'location' => 'Banjarmasin', 'image' => '', 'status' => 'in_stock',
        ],
        [
            'id' => 4, 'name' => 'Bucket Teeth CAT', 'part_number' => 'CAT-BT-950',
            'category' => 'attachment-parts', 'brand' => 'CATERPILLAR', 'stock' => 50,
            'minimum_stock' => 20, 'unit_price' => 125000, 'supplier' => 'CAT Parts Indonesia',
            'location' => 'Palangkaraya', 'image' => '', 'status' => 'in_stock',
        ],
        [
            'id' => 5, 'name' => 'Track Chain Komatsu', 'part_number' => 'KO-TC-D65',
            'category' => 'undercarriage-parts', 'brand' => 'KOMATSU', 'stock' => 3,
            'minimum_stock' => 5, 'unit_price' => 8500000, 'supplier' => 'Komatsu Parts Center',
            'location' => 'Pontianak', 'image' => '', 'status' => 'low_stock',
        ],
        [
            'id' => 6, 'name' => 'Fuel Injector Volvo', 'part_number' => 'VO-FI-EC210',
            'category' => 'engine-parts', 'brand' => 'VOLVO', 'stock' => 0,
            'minimum_stock' => 5, 'unit_price' => 2200000, 'supplier' => 'Volvo CE Parts',
            'location' => 'Banjarbaru', 'image' => '', 'status' => 'out_of_stock',
        ],
    ];
}

function gti_get_spare_parts_categories( $data = [] ) {
    $known = [
        [ 'slug' => 'engine-parts',        'name' => 'Engine Parts',        'icon' => 'fas fa-engine',      'count' => 0 ],
        [ 'slug' => 'hydraulic-parts',     'name' => 'Hydraulic Parts',     'icon' => 'fas fa-water',       'count' => 0 ],
        [ 'slug' => 'undercarriage-parts', 'name' => 'Undercarriage Parts', 'icon' => 'fas fa-cogs',        'count' => 0 ],
        [ 'slug' => 'attachment-parts',    'name' => 'Attachment Parts',    'icon' => 'fas fa-link',        'count' => 0 ],
        [ 'slug' => 'electrical-parts',    'name' => 'Electrical Parts',    'icon' => 'fas fa-bolt',        'count' => 0 ],
        [ 'slug' => 'filter-parts',        'name' => 'Filter Parts',        'icon' => 'fas fa-filter',      'count' => 0 ],
    ];

    $slug_map = [];
    foreach ( $known as &$k ) {
        $slug_map[ $k['slug'] ] = &$k;
    }
    unset( $k );

    $uncategorized = 0;
    foreach ( $data as $item ) {
        $cat = strtolower( trim( $item['category'] ?? '' ) );
        $cat = str_replace( ' ', '-', $cat );
        if ( isset( $slug_map[ $cat ] ) ) {
            $slug_map[ $cat ]['count']++;
        } else {
            $uncategorized++;
        }
    }

    $result = $known;
    if ( $uncategorized > 0 ) {
        $result[] = [ 'slug' => 'others', 'name' => 'Others', 'icon' => 'fas fa-ellipsis-h', 'count' => $uncategorized ];
    }

    return $result;
}

function gti_get_spare_parts_brands( $data = [] ) {
    $brands = array_unique( array_filter( array_column( $data, 'brand' ) ) );
    if ( empty( $brands ) ) {
        $brands = [ 'CAT', 'Komatsu', 'Hitachi', 'Volvo', 'Doosan', 'Hino', 'Kato', 'SANY', 'SDLG', 'XCMG', 'Kobelco', 'Hyundai' ];
    }
    sort( $brands );
    return $brands;
}

function gti_get_spare_parts_suppliers( $data = [] ) {
    $suppliers = array_unique( array_filter( array_column( $data, 'supplier' ) ) );
    sort( $suppliers );
    return $suppliers;
}

function gti_get_spare_parts_locations( $data = [] ) {
    $locs = array_unique( array_filter( array_column( $data, 'location' ) ) );
    if ( empty( $locs ) ) {
        $locs = [ 'Balikpapan', 'Samarinda', 'Banjarmasin', 'Palangkaraya', 'Pontianak', 'Banjarbaru' ];
    }
    sort( $locs );
    return $locs;
}

// ═══════════════════════════════════════════════════════════════════════════
// DETAIL PAGE
// ═══════════════════════════════════════════════════════════════════════════

function gti_spare_render_part_detail( $part_id ) {
    $item = gti_spare_get_part_by_id( $part_id );
    if ( ! $item ) {
        return '<div class="gti-ed-wrapper"><div style="text-align:center;padding:80px 20px;"><h2>Spare part not found.</h2><p><a href="' . esc_url( remove_query_arg( 'id' ) ) . '">&larr; Back to catalog</a></p></div></div>';
    }

    $title       = $item['name'] ?: 'Spare Part';
    $part_number = $item['part_number'] ?: '';
    $brand       = strtoupper( $item['brand'] ?: '' );
    $category    = $item['category'] ?: '';
    $description = $item['description'] ?: '';
    $stock       = (int) $item['stock'];
    $min_stock   = (int) $item['minimum_stock'];
    $price       = $item['unit_price'] ?: '';
    $supplier    = $item['supplier'] ?: '';
    $location    = $item['location'] ?: '—';
    $image       = $item['image'] ?: '';

    // Stock badge
    if ( $stock <= 0 ) {
        $stock_class = 'out-of-stock';
        $stock_label = 'OUT OF STOCK';
    } elseif ( $stock <= $min_stock ) {
        $stock_class = 'low-stock';
        $stock_label = 'LOW STOCK';
    } else {
        $stock_class = 'in-stock';
        $stock_label = 'IN STOCK';
    }

    $wa_number = get_option( 'gti_whatsapp_number', '6281234567890' );

    ob_start();
    ?>
    <div class="gti-ed-wrapper gti-sp-detail" id="gti-spare-parts-detail"
         data-id="<?php echo esc_attr( $part_id ); ?>"
         data-part-number="<?php echo esc_attr( $part_number ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-name="<?php echo esc_attr( $title ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'gti_customer_quotation' ) ); ?>">

        <nav class="gti-ed-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo esc_url( remove_query_arg( 'id' ) ); ?>">Spare Parts</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="current"><?php echo esc_html( $title ); ?></span>
        </nav>

        <!-- ═══ HERO ═══ -->
        <section class="gti-ed-hero">
            <div class="gti-ed-hero-grid">
                <div class="gti-ed-gallery">
                    <div class="gti-ed-main-image-wrapper">
                        <?php if ( $image ) : ?>
                            <img id="gti-ed-main-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                        <?php else : ?>
                            <div class="placeholder-icon"><i class="fas fa-cogs"></i></div>
                        <?php endif; ?>
                        <span class="gti-ed-status-badge <?php echo esc_attr( $stock_class ); ?>"><?php echo esc_html( $stock_label ); ?></span>
                    </div>
                </div>

                <div class="gti-ed-summary">
                    <?php if ( $brand ) : ?>
                        <span class="gti-ed-brand-badge"><?php echo esc_html( $brand ); ?></span>
                    <?php endif; ?>
                    <h1 class="gti-ed-title"><?php echo esc_html( $title ); ?></h1>
                    <?php if ( $part_number ) : ?>
                        <p class="gti-ed-part-number"><strong>Part Number:</strong> <?php echo esc_html( $part_number ); ?></p>
                    <?php endif; ?>
                    <p class="gti-ed-tagline">REQUEST QUOTATION</p>

                    <div class="gti-ed-specs-list">
                        <div class="gti-ed-spec-item"><i class="fas fa-tag"></i><span class="spec-label">Category</span><span class="spec-value"><?php echo esc_html( ucfirst( str_replace( '-', ' ', $category ) ) ); ?></span></div>
                        <div class="gti-ed-spec-item"><i class="fas fa-box"></i><span class="spec-label">Stock</span><span class="spec-value"><?php echo esc_html( $stock ); ?> units (min: <?php echo esc_html( $min_stock ); ?>)</span></div>
                        <div class="gti-ed-spec-item"><i class="fas fa-map-marker-alt"></i><span class="spec-label">Location</span><span class="spec-value"><?php echo esc_html( $location ); ?></span></div>
                        <?php if ( $supplier ) : ?>
                        <div class="gti-ed-spec-item"><i class="fas fa-building"></i><span class="spec-label">Supplier</span><span class="spec-value"><?php echo esc_html( $supplier ); ?></span></div>
                        <?php endif; ?>
                    </div>

                    <?php if ( $price ) : ?>
                    <div class="gti-ed-price-display">
                        <span class="gti-ed-price-label">Unit Price</span>
                        <span class="gti-ed-price-value">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="gti-ed-cta-group">
                        <a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>?text=<?php echo urlencode( 'Halo GTI, saya ingin menanyakan spare part ' . $title . ' (' . $part_number . ')' ); ?>" target="_blank" rel="noopener noreferrer" class="gti-ed-btn gti-ed-btn-whatsapp"><i class="fab fa-whatsapp"></i> CHAT ON WHATSAPP</a>
                        <button type="button" class="gti-ed-btn gti-ed-btn-offer" onclick="document.getElementById('gti-ed-inquiry-form').scrollIntoView({behavior:'smooth'});var f=document.getElementById('ed-name');if(f)f.focus();"><i class="fas fa-file-invoice-dollar"></i> REQUEST QUOTATION</button>
                    </div>
                </div>

                <div class="gti-ed-inquiry-card">
                    <h3 class="gti-ed-inquiry-title">INTERESTED IN THIS PART?</h3>
                    <p class="gti-ed-inquiry-desc">Fill out the form and our team will contact you.</p>
                    <form id="gti-ed-inquiry-form">
                        <input type="hidden" name="gti_quot_nonce" value="<?php echo esc_attr( wp_create_nonce( 'gti_customer_quotation' ) ); ?>">
                        <div class="gti-ed-form-group"><input type="text" name="ed_name" id="ed-name" placeholder="Your Name" required></div>
                        <div class="gti-ed-form-group"><input type="text" name="ed_company" id="ed-company" placeholder="Your Company"></div>
                        <div class="gti-ed-form-group"><input type="tel" name="ed_phone" id="ed-phone" placeholder="Phone / WhatsApp" required></div>
                        <div class="gti-ed-form-group"><input type="email" name="ed_email" id="ed-email" placeholder="Your Email" required></div>
                        <div class="gti-ed-form-group">
                            <textarea name="ed_message" id="ed-message" rows="3" placeholder="I'm interested in <?php echo esc_attr( $title ); ?> (<?php echo esc_attr( $part_number ); ?>), qty: ..."></textarea>
                        </div>
                        <button type="submit" class="gti-ed-form-submit"><i class="fas fa-paper-plane"></i> SEND MESSAGE</button>
                        <div class="gti-ed-form-privacy"><i class="fas fa-lock"></i> Your data is safe with us.</div>
                    </form>
                </div>
            </div>
        </section>

        <!-- ═══ DETAIL SECTION ═══ -->
        <section class="gti-ed-detail-section">
            <div class="gti-ed-detail-grid">
                <div class="gti-ed-specs-block">
                    <h2 class="gti-ed-section-title">PART DETAILS</h2>
                    <div class="gti-ed-spec-table">
                        <div class="gti-ed-spec-row"><span class="label">Part Number</span><span class="value"><?php echo esc_html( $part_number ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Brand</span><span class="value"><?php echo esc_html( $brand ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Category</span><span class="value"><?php echo esc_html( ucfirst( str_replace( '-', ' ', $category ) ) ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Stock</span><span class="value"><?php echo esc_html( $stock ); ?> units</span></div>
                        <div class="gti-ed-spec-row"><span class="label">Minimum Stock</span><span class="value"><?php echo esc_html( $min_stock ); ?> units</span></div>
                        <?php if ( $price ) : ?>
                        <div class="gti-ed-spec-row"><span class="label">Unit Price</span><span class="value">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?></span></div>
                        <?php endif; ?>
                        <?php if ( $supplier ) : ?>
                        <div class="gti-ed-spec-row"><span class="label">Supplier</span><span class="value"><?php echo esc_html( $supplier ); ?></span></div>
                        <?php endif; ?>
                        <div class="gti-ed-spec-row"><span class="label">Location</span><span class="value"><?php echo esc_html( $location ); ?></span></div>
                    </div>
                </div>

                <?php if ( $description ) : ?>
                <div class="gti-ed-features-block">
                    <h2 class="gti-ed-section-title">DESCRIPTION</h2>
                    <div class="gti-ed-description"><?php echo wp_kses_post( nl2br( esc_html( $description ) ) ); ?></div>
                </div>
                <?php endif; ?>

                <div class="gti-ed-why-block">
                    <h2 class="gti-ed-section-title">WHY BUY FROM GTI?</h2>
                    <div class="gti-ed-why-grid">
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-shield-alt"></i></div><span class="gti-ed-why-text">Genuine Parts</span></div>
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-medal"></i></div><span class="gti-ed-why-text">Quality Assured</span></div>
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-box"></i></div><span class="gti-ed-why-text">Ready Stock</span></div>
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-truck"></i></div><span class="gti-ed-why-text">Nationwide Delivery</span></div>
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-headset"></i></div><span class="gti-ed-why-text">Expert Support</span></div>
                        <div class="gti-ed-why-item"><div class="gti-ed-why-icon"><i class="fas fa-tag"></i></div><span class="gti-ed-why-text">Competitive Prices</span></div>
                    </div>
                </div>
            </div>
        </section>

    </div>
    <?php
    return ob_get_clean();
}

function gti_spare_get_part_by_id( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_spare_parts';

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $table_exists ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
        if ( $row ) {
            $stock     = (int) $row->stock;
            $min_stock = (int) $row->minimum_stock;

            if ( $stock <= 0 ) {
                $display_status = 'out_of_stock';
            } elseif ( $stock <= $min_stock ) {
                $display_status = 'low_stock';
            } else {
                $display_status = 'in_stock';
            }

            return [
                'id'            => (int) $row->id,
                'name'          => $row->name ?: 'Spare Part',
                'part_number'   => $row->part_number ?: '',
                'category'      => strtolower( $row->category ?: 'others' ),
                'brand'         => $row->brand ?: '',
                'description'   => $row->description ?: '',
                'stock'         => $stock,
                'minimum_stock' => $min_stock,
                'unit_price'    => $row->unit_price ?: '',
                'supplier'      => $row->supplier ?: '',
                'location'      => $row->location ?: '',
                'image'         => $row->image ?: '',
                'status'        => $display_status,
            ];
        }
    }

    // Fallback: dummy data
    $all_data = gti_get_dummy_spare_parts_data();
    foreach ( $all_data as $item ) {
        if ( (int) $item['id'] === (int) $id ) {
            return $item;
        }
    }

    return null;
}
