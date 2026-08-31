<?php
/**
 * [gti_equipment_filter] Shortcode — Public Equipment Catalog with Filter Panel
 *
 * Usage:
 *   [gti_equipment_filter]
 *   [gti_equipment_filter post_type="gti_equipment" per_page="12"]
 *
 * Design: Left sidebar filter panel + right card grid (matches page-02.png design)
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'gti_equipment_filter', 'gti_render_equipment_filter' );

function gti_render_equipment_filter( $atts = [] ) {
    $defaults = [
        'post_type'  => 'post',
        'per_page'   => 12,
    ];

    $atts = shortcode_atts( $defaults, $atts, 'gti_equipment_filter' );

    $post_type  = sanitize_text_field( $atts['post_type'] );
    $per_page   = absint( $atts['per_page'] );

    // ── Detail Page Mode ───────────────────────────────────────────────
    $equip_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    if ( $equip_id > 0 ) {
        return gti_render_equipment_detail( $equip_id );
    }

    // Get all equipment data for filtering
    $equipment_data = gti_get_equipment_data( $post_type );
    $categories     = gti_get_equipment_categories( $equipment_data );
    $brands         = gti_get_equipment_brands( $equipment_data );
    $types          = gti_get_equipment_types( $equipment_data );
    $locations      = gti_get_equipment_locations( $equipment_data );
    $conditions     = gti_get_equipment_conditions( $equipment_data );

    ob_start();
    ?>
    <div class="gti-ef-wrapper" id="gti-equipment-filter"
         data-post-type="<?php echo esc_attr( $post_type ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'gti_equipment_filter' ) ); ?>"
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

                <!-- Filter Groups -->
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

                    <!-- TYPE -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="type">
                            <h4>TYPE</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="type">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $types as $type ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-type" value="<?php echo esc_attr( $type ); ?>">
                                        <span><?php echo esc_html( $type ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- YEAR -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="year">
                            <h4>YEAR</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="year">
                            <div class="gti-ef-range-inputs">
                                <input type="number" name="ef-year-min" placeholder="Min" min="1990" max="2099">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="number" name="ef-year-max" placeholder="Max" min="1990" max="2099">
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

                    <!-- HOUR METER -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="hours">
                            <h4>HOUR METER</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="hours">
                            <div class="gti-ef-range-inputs">
                                <input type="number" name="ef-hours-min" placeholder="Min hrs" min="0">
                                <span class="gti-ef-range-separator">—</span>
                                <input type="number" name="ef-hours-max" placeholder="Max hrs" min="0">
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

                    <!-- CONDITION -->
                    <div class="gti-ef-filter-group">
                        <div class="gti-ef-filter-header" data-filter="condition">
                            <h4>CONDITION</h4>
                            <span class="toggle-icon"><i class="fas fa-plus"></i></span>
                        </div>
                        <div class="gti-ef-filter-body" data-filter-body="condition">
                            <div class="gti-ef-checkbox-list">
                                <?php foreach ( $conditions as $cond ) : ?>
                                    <label class="gti-ef-checkbox-item">
                                        <input type="checkbox" name="ef-condition" value="<?php echo esc_attr( $cond ); ?>">
                                        <span><?php echo esc_html( $cond ); ?></span>
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
                        Showing <strong>1 - <?php echo esc_html( $per_page ); ?></strong> of <strong><?php echo esc_html( count( $equipment_data ) ); ?></strong> units
                    </div>
                    <div class="gti-ef-topbar-right">
                        <div class="gti-ef-sort-wrapper">
                            <span class="gti-ef-sort-label">Sort by:</span>
                            <select class="gti-ef-sort-select" id="gti-ef-sort">
                                <option value="newest">Newest First</option>
                                <option value="price-low">Price: Low to High</option>
                                <option value="price-high">Price: High to Low</option>
                                <option value="hours-low">Hours: Low to High</option>
                            </select>
                        </div>
                        <div class="gti-ef-view-switcher">
                            <button class="gti-ef-view-btn active" data-view="grid" title="Grid View">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="gti-ef-view-btn" data-view="list" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card Grid -->
                <div class="gti-ef-grid" id="gti-ef-grid">
                    <?php foreach ( array_slice( $equipment_data, 0, $per_page ) as $item ) : ?>
                        <?php gti_render_equipment_card( $item ); ?>
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
                    <h3>No equipment found</h3>
                    <p>Try adjusting your filters or search criteria to find what you're looking for.</p>
                </div>

                <!-- Pagination -->
                <div class="gti-ef-pagination" id="gti-ef-pagination">
                    <?php gti_render_pagination( count( $equipment_data ), $per_page ); ?>
                </div>
            </div>
        </div>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a single equipment card.
 */
function gti_render_equipment_card( $item ) {
    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon':
            case 'coming-soon':
                $status_class = 'coming-soon';
                $status_label = 'COMING SOON';
                break;
            case 'sold':
                $status_class = 'sold';
                $status_label = 'SOLD';
                break;
            default:
                $status_class = 'ready';
                $status_label = 'READY STOCK';
        }
    }

    $title          = isset( $item['title'] ) ? $item['title'] : 'Equipment';
    $year           = isset( $item['year'] ) ? $item['year'] : '—';
    $hours          = isset( $item['hours'] ) ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $location       = isset( $item['location'] ) ? $item['location'] : '—';
    $image_url      = isset( $item['image'] ) ? $item['image'] : '';
    $price          = isset( $item['price'] ) ? $item['price'] : '';
    $id             = isset( $item['id'] ) ? $item['id'] : 0;
    $detail_url     = isset( $item['url'] ) ? $item['url'] : '#';
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'id', $id ) ); ?>" class="gti-ef-card"
         data-id="<?php echo esc_attr( $id ); ?>"
         data-category="<?php echo esc_attr( isset( $item['category'] ) ? $item['category'] : '' ); ?>"
         data-brand="<?php echo esc_attr( isset( $item['brand'] ) ? $item['brand'] : '' ); ?>"
         data-type="<?php echo esc_attr( isset( $item['type'] ) ? $item['type'] : '' ); ?>"
         data-year="<?php echo esc_attr( $year ); ?>"
         data-price="<?php echo esc_attr( $price ); ?>"
         data-hours="<?php echo esc_attr( isset( $item['hours'] ) ? $item['hours'] : '' ); ?>"
         data-location="<?php echo esc_attr( $location ); ?>"
         data-condition="<?php echo esc_attr( isset( $item['condition'] ) ? $item['condition'] : '' ); ?>">

        <!-- Image -->
        <div class="gti-ef-card-image">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="placeholder-icon">
                    <i class="fas fa-truck-monster"></i>
                </div>
            <?php endif; ?>

            <!-- Status Badge -->
            <span class="gti-ef-status-badge <?php echo esc_attr( $status_class ); ?>">
                <?php echo esc_html( $status_label ); ?>
            </span>
        </div>

        <!-- Body -->
        <div class="gti-ef-card-body">
            <h3 class="gti-ef-card-title"><?php echo esc_html( $title ); ?></h3>

            <div class="gti-ef-card-meta">
                <div class="gti-ef-card-meta-row">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo esc_html( $year ); ?></span>
                </div>
                <div class="gti-ef-card-meta-row">
                    <i class="fas fa-tachometer-alt"></i>
                    <span><?php echo esc_html( $hours ); ?></span>
                </div>
                <div class="gti-ef-card-meta-row">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo esc_html( $location ); ?></span>
                </div>
            </div>

            <span class="gti-ef-card-cta">
                DAPATKAN PENAWARAN SPESIAL <i class="fas fa-arrow-right"></i>
            </span>
        </div>
    </a>
    <?php
}

/**
 * Render pagination controls.
 */
function gti_render_pagination( $total, $per_page ) {
    $total_pages = max( 1, ceil( $total / $per_page ) );
    $current     = 1;

    // Previous
    echo '<button class="gti-ef-page-btn" data-page="prev" disabled><i class="fas fa-chevron-left"></i></button>';

    // Page numbers
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

    // Next
    $next_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="next"' . $next_disabled . '><i class="fas fa-chevron-right"></i></button>';

    // Last page
    $last_disabled = $current >= $total_pages ? ' disabled' : '';
    echo '<button class="gti-ef-page-btn" data-page="last"' . $last_disabled . '><i class="fas fa-angle-double-right"></i></button>';
}

/**
 * Get equipment data from the custom gti_equipment table.
 */
function gti_get_equipment_data( $post_type = 'post' ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';

    // Check if table exists
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $table_exists ) {
        return [];
    }

    $rows = $wpdb->get_results(
        "SELECT * FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC"
    );

    // Fallback: use dummy data if table is empty
    if ( empty( $rows ) && function_exists( 'gti_get_dummy_equipment_data' ) ) {
        return gti_get_dummy_equipment_data();
    }

    $items = [];
    foreach ( $rows as $row ) {
        // Parse main_image from JSON if needed
        $image = '';
        if ( ! empty( $row->main_image ) ) {
            $image = $row->main_image;
        } elseif ( ! empty( $row->images ) ) {
            $imgs = json_decode( $row->images, true );
            if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                $image = $imgs[0];
            }
        }

        // Map status to display label
        $status = $row->status ?: 'available';
        switch ( strtolower( $status ) ) {
            case 'sold':
                $display_status = 'sold';
                break;
            case 'coming_soon':
            case 'coming-soon':
                $display_status = 'coming_soon';
                break;
            default:
                $display_status = 'ready_stock';
        }

        $items[] = [
            'id'            => (int) $row->id,
            'title'         => $row->name ?: 'Equipment',
            'url'           => '#',
            'category'      => strtolower( $row->category ?: 'others' ),
            'brand'         => $row->brand ?: '',
            'type'          => $row->category ?: '',
            'year'          => $row->year ?: '',
            'price'         => $row->price ?: '',
            'hours'         => $row->hours ?: '',
            'location'      => $row->location ?: '',
            'condition'     => $row->condition_status ?: '',
            'status'        => $display_status,
            'image'         => $image,
            'is_wishlisted' => false,
        ];
    }

    return $items;
}

/**
 * Get equipment categories with counts from provided data.
 */
function gti_get_equipment_categories( $data = [] ) {
    $known = [
        [ 'slug' => 'excavator',    'name' => 'Excavator',    'icon' => 'fas fa-dumpster',       'count' => 0 ],
        [ 'slug' => 'bulldozer',    'name' => 'Bulldozer',    'icon' => 'fas fa-tractor',        'count' => 0 ],
        [ 'slug' => 'wheel-loader', 'name' => 'Wheel Loader', 'icon' => 'fas fa-truck-monster',  'count' => 0 ],
        [ 'slug' => 'dump-truck',   'name' => 'Dump Truck',   'icon' => 'fas fa-truck',          'count' => 0 ],
        [ 'slug' => 'motor-grader', 'name' => 'Motor Grader', 'icon' => 'fas fa-road',           'count' => 0 ],
        [ 'slug' => 'crane',        'name' => 'Crane',        'icon' => 'fas fa-people-carry',   'count' => 0 ],
        [ 'slug' => 'forklift',     'name' => 'Forklift',     'icon' => 'fas fa-boxes',          'count' => 0 ],
    ];

    $slug_map = [];
    foreach ( $known as &$k ) {
        $slug_map[ $k['slug'] ] = &$k;
    }
    unset( $k );

    // Count from data + collect uncategorized
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

/**
 * Get unique brands from provided equipment data.
 * Falls back to known brands if data is empty.
 */
function gti_get_equipment_brands( $data = [] ) {
    $brands = array_unique( array_filter( array_column( $data, 'brand' ) ) );
    if ( empty( $brands ) ) {
        $brands = [ 'CAT', 'Komatsu', 'Hitachi', 'Volvo', 'Doosan', 'Hino', 'Kato', 'SANY', 'SDLG', 'XCMG', 'Kobelco', 'Hyundai' ];
    }
    sort( $brands );
    return $brands;
}

/**
 * Get unique types from provided equipment data.
 * Falls back to known types if data is empty.
 */
function gti_get_equipment_types( $data = [] ) {
    $types = array_unique( array_filter( array_column( $data, 'type' ) ) );
    if ( empty( $types ) ) {
        $types = [ 'Excavator', 'Bulldozer', 'Wheel Loader', 'Dump Truck', 'Motor Grader', 'Crane', 'Forklift' ];
    }
    sort( $types );
    return $types;
}

/**
 * Get unique locations from provided equipment data.
 * Falls back to known locations if data is empty.
 */
function gti_get_equipment_locations( $data = [] ) {
    $locs = array_unique( array_filter( array_column( $data, 'location' ) ) );
    if ( empty( $locs ) ) {
        $locs = [ 'Balikpapan', 'Samarinda', 'Banjarmasin', 'Palangkaraya', 'Pontianak', 'Banjarbaru' ];
    }
    sort( $locs );
    return $locs;
}

/**
 * Get unique conditions from provided equipment data.
 * Falls back to known values if data is empty.
 */
function gti_get_equipment_conditions( $data = [] ) {
    $conds = array_unique( array_filter( array_column( $data, 'condition' ) ) );

    // Fallback: common condition values if no data exists
    if ( empty( $conds ) ) {
        $conds = [ 'Excellent', 'Good', 'Fair', 'Poor' ];
    }

    // Normalize: capitalize first letter
    $conds = array_map( function( $c ) {
        return ucfirst( strtolower( $c ) );
    }, $conds );

    $conds = array_unique( $conds );
    sort( $conds );
    return $conds;
}

/**
 * Guess brand from title (fallback when no meta data).
 */
function gti_guess_brand_from_title( $title ) {
    $brands = [
        'Komatsu'   => [ 'Komatsu', 'PC200', 'PC300', 'D65', 'WA' ],
        'CAT'       => [ 'CAT', 'Caterpillar', '320', 'D6', '950' ],
        'Hitachi'   => [ 'Hitachi', 'Zaxis', 'ZX' ],
        'Volvo'     => [ 'Volvo', 'EC', 'L' ],
        'Doosan'    => [ 'Doosan', 'DX' ],
        'Hino'      => [ 'Hino', 'FM', 'FN' ],
        'Kato'      => [ 'Kato', 'HD' ],
        'SANY'      => [ 'SANY', 'SY' ],
        'SDLG'      => [ 'SDLG', 'LG' ],
        'XCMG'      => [ 'XCMG', 'XE' ],
        'Kobelco'   => [ 'Kobelco', 'SK' ],
        'Hyundai'   => [ 'Hyundai', 'R' ],
        'Sakai'     => [ 'Sakai', 'SW' ],
    ];

    $title_upper = strtoupper( $title );
    foreach ( $brands as $brand => $keywords ) {
        foreach ( $keywords as $kw ) {
            if ( stripos( $title, $kw ) !== false ) {
                return $brand;
            }
        }
    }
    return '';
}

/**
 * Guess type from title (fallback).
 */
function gti_guess_type_from_title( $title ) {
    $title_lower = strtolower( $title );
    $types = [
        'Excavator'    => [ 'excavator', 'pc200', 'pc300', 'zx200', 'zx300', 'ec200', 'ec300', '320', 'dx', 'sy215' ],
        'Bulldozer'    => [ 'bulldozer', 'd65', 'd6', 'd5' ],
        'Wheel Loader' => [ 'wheel loader', 'wa', 'lg936', '950' ],
        'Dump Truck'   => [ 'dump truck', 'hino', 'fm ', 'fn ' ],
        'Motor Grader' => [ 'motor grader', 'grader', 'gd' ],
        'Crane'        => [ 'crane', 'kato' ],
        'Forklift'     => [ 'forklift' ],
    ];

    foreach ( $types as $type => $keywords ) {
        foreach ( $keywords as $kw ) {
            if ( stripos( $title_lower, $kw ) !== false ) {
                return $type;
            }
        }
    }
    return 'Others';
}

/**
 * Render the equipment detail page.
 *
 * @param int $equipment_id Equipment row ID from gti_equipment table.
 * @return string HTML output.
 */
function gti_render_equipment_detail( $equipment_id ) {
    $item = gti_get_equipment_by_id( $equipment_id );
    if ( ! $item ) {
        return '<div class="gti-ed-wrapper"><div style="text-align:center;padding:80px 20px;"><h2>Equipment not found.</h2><p><a href="' . esc_url( remove_query_arg( 'id' ) ) . '">&larr; Back to catalog</a></p></div></div>';
    }

    // Prepare data
    $brand          = strtoupper( $item['brand'] ?: '' );
    $model          = $item['model'] ?: '';
    $title          = $item['title'] ?: 'Equipment';
    $year           = $item['year'] ?: '—';
    $hours          = $item['hours'] ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $hours_raw      = $item['hours'] ?: '';
    $condition      = $item['condition'] ?: '—';
    $location       = $item['location'] ?: '—';
    $price          = $item['price'] ?: '';
    $image          = $item['image'] ?: '';
    $category       = $item['category'] ?: '';
    $description    = $item['description'] ?: '';
    $equipment_code = $item['equipment_code'] ?: '';

    // Status badge
    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon':
            case 'coming-soon':
                $status_class = 'coming-soon';
                $status_label = 'COMING SOON';
                break;
            case 'sold':
                $status_class = 'sold';
                $status_label = 'SOLD';
                break;
            default:
                $status_class = 'ready';
                $status_label = 'READY STOCK';
        }
    }

    // Gallery images — use main image + any extra images from data
    $gallery_images = [];
    if ( ! empty( $item['image'] ) ) {
        $gallery_images[] = $item['image'];
    }
    $max_thumbs = 5;
    $display_gallery = array_slice( $gallery_images, 0, $max_thumbs );

    // Features list
    $features = [
        'Good Undercarriage',
        'Strong Engine Performance',
        'All Functions Normal',
        'Ready to Work',
        'Regularly Serviced',
    ];
    if ( $condition && $condition !== '—' ) {
        array_unshift( $features, $condition . ' Condition' );
    }

    // Get related equipment (same category, exclude current)
    $related = gti_get_related_equipment( $item, 8 );

    // WhatsApp number (from options or default)
    $wa_number = get_option( 'gti_whatsapp_number', '6281234567890' );

    ob_start();
    ?>
    <div class="gti-ed-wrapper" id="gti-equipment-detail"
         data-id="<?php echo esc_attr( $equipment_id ); ?>"
         data-brand="<?php echo esc_attr( $brand ); ?>"
         data-model="<?php echo esc_attr( $model ); ?>"
         data-wa="<?php echo esc_attr( $wa_number ); ?>">

        <!-- Breadcrumb -->
        <nav class="gti-ed-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo esc_url( remove_query_arg( 'id' ) ); ?>">Equipment</a>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <span class="current"><?php echo esc_html( $title ); ?></span>
        </nav>

        <!-- ═══ HERO ═══════════════════════════════════════════════════════ -->
        <section class="gti-ed-hero">
            <div class="gti-ed-hero-grid">

                <!-- ── Gallery ──────────────────────────────────────────── -->
                <div class="gti-ed-gallery">
                    <div class="gti-ed-main-image-wrapper">
                        <?php if ( $image ) : ?>
                            <img id="gti-ed-main-image" src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
                        <?php else : ?>
                            <div class="placeholder-icon"><i class="fas fa-truck-monster"></i></div>
                        <?php endif; ?>
                        <span class="gti-ed-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
                        <button class="gti-ed-gallery-arrow prev" type="button" aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
                        <button class="gti-ed-gallery-arrow next" type="button" aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <!-- Thumbnails (4 visible + "+N More" overlay) -->
                    <div class="gti-ed-thumbnails">
                        <?php if ( ! empty( $display_gallery ) ) : ?>
                            <?php $visible_count = min( 4, count( $display_gallery ) ); ?>
                            <?php for ( $idx = 0; $idx < $visible_count; $idx++ ) : ?>
                                <?php $img = $display_gallery[ $idx ]; ?>
                                <div class="gti-ed-thumb <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $idx ); ?>" data-src="<?php echo esc_url( $img ); ?>">
                                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title . ' photo ' . ( $idx + 1 ) ); ?>">
                                </div>
                            <?php endfor; ?>
                            <?php if ( count( $display_gallery ) > 4 ) : ?>
                                <?php $extra = count( $display_gallery ) - 4; ?>
                                <div class="gti-ed-thumb gti-ed-thumb-more">
                                    <img src="<?php echo esc_url( $display_gallery[4] ?? $display_gallery[0] ); ?>" alt="More photos">
                                    <span class="gti-ed-thumb-overlay">+<?php echo esc_html( $extra ); ?> More</span>
                                </div>
                            <?php endif; ?>
                        <?php else : ?>
                            <!-- Placeholder thumbnails (4 + overlay) -->
                            <div class="gti-ed-thumb active" data-index="0">
                                <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                            </div>
                            <div class="gti-ed-thumb" data-index="1">
                                <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                            </div>
                            <div class="gti-ed-thumb" data-index="2">
                                <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                            </div>
                            <div class="gti-ed-thumb" data-index="3">
                                <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                            </div>
                            <div class="gti-ed-thumb gti-ed-thumb-more">
                                <div class="gti-ed-thumb-placeholder"><i class="fas fa-image"></i></div>
                                <span class="gti-ed-thumb-overlay">+12 More</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Product Summary ──────────────────────────────────── -->
                <div class="gti-ed-summary">
                    <?php if ( $brand ) : ?>
                        <span class="gti-ed-brand-badge"><?php echo esc_html( $brand ); ?></span>
                    <?php endif; ?>
                    <h1 class="gti-ed-title"><?php echo esc_html( $title ); ?></h1>
                    <p class="gti-ed-tagline">DAPATKAN PENAWARAN SPESIAL</p>

                    <div class="gti-ed-specs-list">
                        <div class="gti-ed-spec-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span class="spec-label">Year</span>
                            <span class="spec-value"><?php echo esc_html( $year ); ?></span>
                        </div>
                        <div class="gti-ed-spec-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="spec-label">Working Hour</span>
                            <span class="spec-value"><?php echo esc_html( $hours ); ?></span>
                        </div>
                        <div class="gti-ed-spec-item">
                            <i class="fas fa-check-circle"></i>
                            <span class="spec-label">Condition</span>
                            <span class="spec-value"><?php echo esc_html( $condition ); ?></span>
                        </div>
                        <div class="gti-ed-spec-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span class="spec-label">Location</span>
                            <span class="spec-value"><?php echo esc_html( $location ); ?>, Indonesia</span>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="gti-ed-cta-group">
                        <a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>?text=<?php echo urlencode( 'Halo GTI, saya tertarik dengan unit ' . $title ); ?>"
                           target="_blank" rel="noopener noreferrer" class="gti-ed-btn gti-ed-btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> CHAT ON WHATSAPP
                        </a>
                        <button type="button" class="gti-ed-btn gti-ed-btn-offer" onclick="document.getElementById('gti-ed-inquiry-form').scrollIntoView({behavior:'smooth'});">
                            <i class="fas fa-handshake"></i> MAKE AN OFFER
                        </button>
                        <button type="button" id="gti-ed-share-btn" class="gti-ed-btn gti-ed-btn-share">
                            <i class="fas fa-share-alt"></i> <span>SHARE UNIT</span>
                        </button>
                    </div>
                </div>

                <!-- ── Inquiry Form ─────────────────────────────────────── -->
                <div class="gti-ed-inquiry-card">
                    <h3 class="gti-ed-inquiry-title">INTERESTED IN THIS UNIT?</h3>
                    <p class="gti-ed-inquiry-desc">Fill out the form and our team will contact you.</p>
                    <form id="gti-ed-inquiry-form">
                        <div class="gti-ed-form-group">
                            <input type="text" name="ed_name" id="ed-name" placeholder="Your Name" required>
                        </div>
                        <div class="gti-ed-form-group">
                            <input type="text" name="ed_company" id="ed-company" placeholder="Your Company">
                        </div>
                        <div class="gti-ed-form-group">
                            <input type="tel" name="ed_phone" id="ed-phone" placeholder="Phone / WhatsApp" required>
                        </div>
                        <div class="gti-ed-form-group">
                            <input type="email" name="ed_email" id="ed-email" placeholder="Your Email">
                        </div>
                        <div class="gti-ed-form-group">
                            <textarea name="ed_message" id="ed-message" rows="3" placeholder="Your Message"></textarea>
                        </div>
                        <button type="submit" class="gti-ed-form-submit">
                            <i class="fas fa-paper-plane"></i> SEND MESSAGE
                        </button>
                        <div class="gti-ed-form-privacy">
                            <i class="fas fa-lock"></i> Your data is safe with us.
                        </div>
                    </form>
                </div>

            </div>
        </section>

        <!-- ═══ DETAIL SECTION ═══════════════════════════════════════════ -->
        <section class="gti-ed-detail-section">
            <div class="gti-ed-detail-grid">

                <!-- ── Specifications ────────────────────────────────────── -->
                <div class="gti-ed-specs-block">
                    <h2 class="gti-ed-section-title">SPECIFICATIONS</h2>
                    <div class="gti-ed-spec-table">
                        <div class="gti-ed-spec-row"><span class="label">Brand</span><span class="value"><?php echo esc_html( $brand ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Model</span><span class="value"><?php echo esc_html( $model ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Type</span><span class="value"><?php echo esc_html( $category ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Year</span><span class="value"><?php echo esc_html( $year ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Condition</span><span class="value"><?php echo esc_html( $condition ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Working Hours</span><span class="value"><?php echo esc_html( $hours ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Location</span><span class="value"><?php echo esc_html( $location ); ?></span></div>
                        <div class="gti-ed-spec-row"><span class="label">Unit Code</span><span class="value"><?php echo esc_html( $equipment_code ); ?></span></div>
                        <?php if ( $price ) : ?>
                        <div class="gti-ed-spec-row"><span class="label">Price</span><span class="value">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Features ──────────────────────────────────────────── -->
                <div class="gti-ed-features-block">
                    <h2 class="gti-ed-section-title">FEATURES</h2>
                    <?php foreach ( $features as $feat ) : ?>
                        <div class="gti-ed-feature-item">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo esc_html( $feat ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ── Why Buy from GTI ─────────────────────────────────── -->
                <div class="gti-ed-why-block">
                    <h2 class="gti-ed-section-title">WHY BUY FROM GTI?</h2>
                    <div class="gti-ed-why-grid">
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-shield-alt"></i></div>
                            <span class="gti-ed-why-text">All Units Inspected</span>
                        </div>
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-medal"></i></div>
                            <span class="gti-ed-why-text">Quality Assurance</span>
                        </div>
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-box"></i></div>
                            <span class="gti-ed-why-text">Ready Stock</span>
                        </div>
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-truck"></i></div>
                            <span class="gti-ed-why-text">Nationwide Delivery</span>
                        </div>
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-headset"></i></div>
                            <span class="gti-ed-why-text">After Sales Support</span>
                        </div>
                        <div class="gti-ed-why-item">
                            <div class="gti-ed-why-icon"><i class="fas fa-tag"></i></div>
                            <span class="gti-ed-why-text">Competitive Price</span>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ═══ RELATED PRODUCTS ══════════════════════════════════════════ -->
        <?php if ( ! empty( $related ) ) : ?>
        <section class="gti-ed-related-section">
            <div class="gti-ed-related-header">
                <h2>YOU MAY ALSO LIKE</h2>
                <a href="<?php echo esc_url( remove_query_arg( 'id' ) ); ?>">VIEW ALL &rarr;</a>
            </div>
            <div class="gti-ed-related-carousel-wrapper">
                <button class="gti-ed-carousel-arrow prev" type="button" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
                <div class="gti-ed-related-carousel" id="gti-ed-related-carousel">
                    <?php foreach ( $related as $rel ) : ?>
                        <?php gti_render_related_card( $rel ); ?>
                    <?php endforeach; ?>
                </div>
                <button class="gti-ed-carousel-arrow next" type="button" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
        <?php endif; ?>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render a single related product card.
 */
function gti_render_related_card( $item ) {
    $status_class = 'ready';
    $status_label = 'READY STOCK';
    if ( isset( $item['status'] ) ) {
        switch ( strtolower( $item['status'] ) ) {
            case 'coming_soon':
            case 'coming-soon':
                $status_class = 'coming-soon';
                $status_label = 'COMING SOON';
                break;
            case 'sold':
                $status_class = 'sold';
                $status_label = 'SOLD';
                break;
            default:
                $status_class = 'ready';
                $status_label = 'READY STOCK';
        }
    }

    $title      = isset( $item['title'] ) ? $item['title'] : 'Equipment';
    $year       = isset( $item['year'] ) ? $item['year'] : '—';
    $hours      = isset( $item['hours'] ) ? number_format( (float) $item['hours'], 0 ) . ' Hrs' : '—';
    $location   = isset( $item['location'] ) ? $item['location'] : '—';
    $image_url  = isset( $item['image'] ) ? $item['image'] : '';
    $id         = isset( $item['id'] ) ? $item['id'] : 0;
    $price      = isset( $item['price'] ) ? $item['price'] : '';
    ?>
    <a href="<?php echo esc_url( add_query_arg( 'id', $id ) ); ?>" class="gti-ed-related-card">
        <div class="gti-ed-related-card-image">
            <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
            <?php else : ?>
                <div class="placeholder-icon"><i class="fas fa-truck-monster"></i></div>
            <?php endif; ?>
            <span class="gti-ed-related-card-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
        </div>
        <div class="gti-ed-related-card-body">
            <h3 class="gti-ed-related-card-title"><?php echo esc_html( $title ); ?></h3>
            <div class="gti-ed-related-card-meta">
                <div class="gti-ed-related-card-meta-row">
                    <i class="fas fa-calendar-alt"></i> <span><?php echo esc_html( $year ); ?></span>
                </div>
                <div class="gti-ed-related-card-meta-row">
                    <i class="fas fa-tachometer-alt"></i> <span><?php echo esc_html( $hours ); ?></span>
                </div>
                <div class="gti-ed-related-card-meta-row">
                    <i class="fas fa-map-marker-alt"></i> <span><?php echo esc_html( $location ); ?></span>
                </div>
            </div>
            <?php if ( $price ) : ?>
            <div class="gti-ed-related-card-price">Rp <?php echo esc_html( number_format( (float) $price, 0, ',', '.' ) ); ?></div>
            <?php endif; ?>
            <span class="gti-ed-related-card-cta">DAPATKAN PENAWARAN SPESIAL</span>
        </div>
    </a>
    <?php
}

/**
 * Get a single equipment item by ID.
 */
function gti_get_equipment_by_id( $id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $table_exists ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $id ) );
        if ( $row ) {
            $image = '';
            if ( ! empty( $row->main_image ) ) {
                $image = $row->main_image;
            } elseif ( ! empty( $row->images ) ) {
                $imgs = json_decode( $row->images, true );
                if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                    $image = $imgs[0];
                }
            }

            $status = $row->status ?: 'available';
            switch ( strtolower( $status ) ) {
                case 'sold':
                    $display_status = 'sold';
                    break;
                case 'coming_soon':
                case 'coming-soon':
                    $display_status = 'coming_soon';
                    break;
                default:
                    $display_status = 'ready_stock';
            }

            return [
                'id'            => (int) $row->id,
                'equipment_code'=> $row->equipment_code ?: '',
                'title'         => $row->name ?: 'Equipment',
                'url'           => '#',
                'category'      => strtolower( $row->category ?: 'others' ),
                'brand'         => $row->brand ?: '',
                'model'         => $row->model ?: '',
                'type'          => $row->category ?: '',
                'year'          => $row->year ?: '',
                'price'         => $row->price ?: '',
                'hours'         => $row->hours ?: '',
                'location'      => $row->location ?: '',
                'condition'     => $row->condition_status ?: '',
                'status'        => $display_status,
                'image'         => $image,
                'description'   => $row->description ?: '',
                'is_wishlisted' => false,
            ];
        }
    }

    // Fallback: check dummy data (assign sequential IDs matching insertion order)
    $all_data = gti_get_dummy_equipment_data();
    foreach ( $all_data as $idx => $item ) {
        $dummy_id = $idx + 1;
        if ( $dummy_id === (int) $id ) {
            $item['id'] = $dummy_id;
            return $item;
        }
    }

    return null;
}

/**
 * Get related equipment by category (exclude current item).
 */
function gti_get_related_equipment( $current, $limit = 8 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';
    $items = [];

    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( $table_exists ) {
        $category = $current['category'] ?: '';
        $current_id = $current['id'];

        if ( $category ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE deleted_at IS NULL AND LOWER(category) = LOWER(%s) AND id != %d ORDER BY created_at DESC LIMIT %d",
                    $category,
                    $current_id,
                    $limit
                )
            );

            foreach ( $rows as $row ) {
                $image = '';
                if ( ! empty( $row->main_image ) ) {
                    $image = $row->main_image;
                } elseif ( ! empty( $row->images ) ) {
                    $imgs = json_decode( $row->images, true );
                    if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                        $image = $imgs[0];
                    }
                }

                $status = $row->status ?: 'available';
                switch ( strtolower( $status ) ) {
                    case 'sold':
                        $display_status = 'sold';
                        break;
                    case 'coming_soon':
                    case 'coming-soon':
                        $display_status = 'coming_soon';
                        break;
                    default:
                        $display_status = 'ready_stock';
                }

                $items[] = [
                    'id'       => (int) $row->id,
                    'title'    => $row->name ?: 'Equipment',
                    'category' => strtolower( $row->category ?: 'others' ),
                    'brand'    => $row->brand ?: '',
                    'model'    => $row->model ?: '',
                    'year'     => $row->year ?: '',
                    'price'    => $row->price ?: '',
                    'hours'    => $row->hours ?: '',
                    'location' => $row->location ?: '',
                    'condition'=> $row->condition_status ?: '',
                    'status'   => $display_status,
                    'image'    => $image,
                ];
            }
        }

        // Pad with other equipment if not enough same-category
        if ( count( $items ) < $limit ) {
            $existing_ids = array_column( $items, 'id' );
            $existing_ids[] = $current['id'];
            $placeholders = implode( ',', array_fill( 0, count( $existing_ids ), '%d' ) );

            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE deleted_at IS NULL AND id NOT IN ({$placeholders}) ORDER BY created_at DESC LIMIT %d",
                    array_merge( $existing_ids, [ $limit - count( $items ) ] )
                )
            );

            foreach ( $rows as $row ) {
                $image = '';
                if ( ! empty( $row->main_image ) ) {
                    $image = $row->main_image;
                } elseif ( ! empty( $row->images ) ) {
                    $imgs = json_decode( $row->images, true );
                    if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                        $image = $imgs[0];
                    }
                }

                $status = $row->status ?: 'available';
                switch ( strtolower( $status ) ) {
                    case 'sold':
                        $display_status = 'sold';
                        break;
                    case 'coming_soon':
                    case 'coming-soon':
                        $display_status = 'coming_soon';
                        break;
                    default:
                        $display_status = 'ready_stock';
                }

                $items[] = [
                    'id'       => (int) $row->id,
                    'title'    => $row->name ?: 'Equipment',
                    'category' => strtolower( $row->category ?: 'others' ),
                    'brand'    => $row->brand ?: '',
                    'model'    => $row->model ?: '',
                    'year'     => $row->year ?: '',
                    'price'    => $row->price ?: '',
                    'hours'    => $row->hours ?: '',
                    'location' => $row->location ?: '',
                    'condition'=> $row->condition_status ?: '',
                    'status'   => $display_status,
                    'image'    => $image,
                ];
            }
        }
    }

    // Fallback to dummy data if DB returned nothing
    if ( empty( $items ) ) {
        $all_data = gti_get_dummy_equipment_data();
        $current_id = $current['id'] ?? 0;
        foreach ( $all_data as $idx => $d ) {
            $dummy_id = $idx + 1;
            if ( $dummy_id !== (int) $current_id ) {
                $d['id'] = $dummy_id;
                $items[] = $d;
                if ( count( $items ) >= $limit ) break;
            }
        }
    }

    return $items;
}
