<?php
/**
 * OS_Export_Service — Excel and print exports via PhpSpreadsheet.
 * Reuses Olama School's Composer vendor/autoload.php when available.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class OS_Export_Service {

    /**
     * Ensure PhpSpreadsheet is available.
     * Reuses School's already-loaded Composer autoloader; only loads Stores'
     * own vendor/ if needed (avoids duplicate class definition errors).
     */
    private static function ensure_spreadsheet() {
        if ( class_exists( 'PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
            return true; // School plugin already loaded it
        }
        $vendor = OS_PATH . 'vendor/autoload.php';
        if ( file_exists( $vendor ) ) {
            require_once $vendor;
            return true;
        }
        return false;
    }

    /**
     * Export an array of data rows to an Excel file and stream it to the browser.
     *
     * @param  string $filename  Output filename (without .xlsx).
     * @param  array  $headers   Column header labels.
     * @param  array  $rows      Array of arrays — one per row.
     * @param  string $sheet_title
     */
    public static function export_xlsx( $filename, $headers, $rows, $sheet_title = 'Sheet1' ) {
        if ( ! self::ensure_spreadsheet() ) {
            wp_die( __( 'PhpSpreadsheet is not available. Please ensure Composer dependencies are installed.', 'olama-stores' ) );
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle( $sheet_title );

        // Write headers
        foreach ( $headers as $col => $label ) {
            $sheet->setCellValueByColumnAndRow( $col + 1, 1, $label );
        }

        // Write data
        foreach ( $rows as $row_idx => $row ) {
            foreach ( array_values( $row ) as $col => $value ) {
                $sheet->setCellValueByColumnAndRow( $col + 1, $row_idx + 2, $value );
            }
        }

        // Style header row
        $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( count( $headers ) );
        $sheet->getStyle( "A1:{$last_col}1" )->getFont()->setBold( true );

        // Auto-size columns
        foreach ( range( 'A', $last_col ) as $col_letter ) {
            $sheet->getColumnDimension( $col_letter )->setAutoSize( true );
        }

        // Stream
        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '.xlsx"' );
        header( 'Cache-Control: max-age=0' );

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
        $writer->save( 'php://output' );
        exit;
    }

    /**
     * Export current stock balance report.
     *
     * @param  array $args  Same args as OS_Stock_Service::get_stock_levels().
     */
    public static function export_stock_balance( $args = array() ) {
        $rows    = OS_Stock_Service::get_stock_levels( $args );
        $headers = array(
            __( 'SKU', 'olama-stores' ),
            __( 'Item Name', 'olama-stores' ),
            __( 'Category', 'olama-stores' ),
            __( 'Warehouse', 'olama-stores' ),
            __( 'On Hand', 'olama-stores' ),
            __( 'Reserved', 'olama-stores' ),
            __( 'Available', 'olama-stores' ),
            __( 'Min Level', 'olama-stores' ),
            __( 'Unit', 'olama-stores' ),
        );
        $data = array_map( function ( $r ) {
            return array(
                $r->sku, $r->name, $r->category_name, $r->warehouse_name,
                $r->quantity_on_hand, $r->quantity_reserved,
                // Correction #4: calculated in PHP, not GENERATED column
                os_qty_available( $r->quantity_on_hand, $r->quantity_reserved ),
                $r->min_stock_level, $r->unit_symbol,
            );
        }, $rows );

        self::export_xlsx( 'stock-balance-' . date( 'Y-m-d' ), $headers, $data, 'Stock Balance' );
    }

    /**
     * Export assignments list.
     *
     * @param  array $args  Filters: assignee_type, status, academic_year_id.
     */
    public static function export_assignments( $args = array() ) {
        $rows    = OS_Assignment::get_list( $args );
        $headers = array(
            __( 'ID', 'olama-stores' ),
            __( 'Type', 'olama-stores' ),
            __( 'Assignee', 'olama-stores' ),
            __( 'Item', 'olama-stores' ),
            __( 'Warehouse', 'olama-stores' ),
            __( 'Qty Assigned', 'olama-stores' ),
            __( 'Qty Returned', 'olama-stores' ),
            __( 'Status', 'olama-stores' ),
            __( 'Assigned Date', 'olama-stores' ),
        );
        $data = array_map( function ( $r ) {
            return array(
                $r->id, $r->assignee_type,
                OS_School_Integration::get_assignee_label( $r->assignee_type, $r->assignee_id ),
                $r->item_name, $r->warehouse_name,
                $r->quantity_assigned, $r->quantity_returned,
                $r->status, $r->assigned_date,
            );
        }, $rows );

        self::export_xlsx( 'assignments-' . date( 'Y-m-d' ), $headers, $data, 'Assignments' );
    }
}
