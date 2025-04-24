<?php
namespace TechOpsContentSync;

/**
 * Sync History Class
 * 
 * Manages sync history records and package status.
 */
class Sync_History {
    private $wpdb;
    private $history_table;
    private $packages_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->history_table = $wpdb->prefix . 'techops_sync_history';
        $this->packages_table = $wpdb->prefix . 'techops_sync_packages';
    }

    /**
     * Create a new sync record
     */
    public function create_sync_record($repository_url, $branch) {
        $sync_id = wp_generate_uuid4();
        
        $this->wpdb->insert(
            $this->history_table,
            [
                'sync_id' => $sync_id,
                'repository_url' => $repository_url,
                'branch' => $branch,
                'status' => 'pending',
                'started_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        return $sync_id;
    }

    /**
     * Update sync status
     */
    public function update_sync_status($sync_id, $status, $error_message = null) {
        $data = [
            'status' => $status
        ];
        $format = ['%s'];

        if ($status === 'completed' || $status === 'failed') {
            $data['completed_at'] = current_time('mysql');
            $format[] = '%s';
        }

        if ($error_message) {
            $data['error_message'] = $error_message;
            $format[] = '%s';
        }

        $this->wpdb->update(
            $this->history_table,
            $data,
            ['sync_id' => $sync_id],
            $format,
            ['%s']
        );
    }

    /**
     * Add package status
     */
    public function add_package_status($sync_id, $package_name, $package_type, $status, $error_message = null) {
        $this->wpdb->insert(
            $this->packages_table,
            [
                'sync_id' => $sync_id,
                'package_name' => $package_name,
                'package_type' => $package_type,
                'status' => $status,
                'error_message' => $error_message
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get sync history
     */
    public function get_sync_history($limit = 10, $offset = 0) {
        $query = "SELECT h.*, 
                    COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.id END) as completed_packages,
                    COUNT(DISTINCT CASE WHEN p.status = 'failed' THEN p.id END) as failed_packages,
                    COUNT(DISTINCT p.id) as total_packages
                 FROM {$this->history_table} h
                 LEFT JOIN {$this->packages_table} p ON h.sync_id = p.sync_id
                 GROUP BY h.id
                 ORDER BY h.started_at DESC
                 LIMIT %d OFFSET %d";

        return $this->wpdb->get_results(
            $this->wpdb->prepare($query, $limit, $offset)
        );
    }

    /**
     * Get sync details
     */
    public function get_sync_details($sync_id) {
        $history = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->history_table} WHERE sync_id = %s",
                $sync_id
            )
        );

        if (!$history) {
            return null;
        }

        $packages = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->packages_table} WHERE sync_id = %s",
                $sync_id
            )
        );

        return [
            'history' => $history,
            'packages' => $packages
        ];
    }

    /**
     * Delete sync record
     */
    public function delete_sync_record($sync_id) {
        // Delete packages first due to foreign key
        $this->wpdb->delete(
            $this->packages_table,
            ['sync_id' => $sync_id],
            ['%s']
        );

        // Delete history record
        $this->wpdb->delete(
            $this->history_table,
            ['sync_id' => $sync_id],
            ['%s']
        );
    }

    /**
     * Clean old records
     */
    public function clean_old_records($days = 30) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Delete old packages
        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE p FROM {$this->packages_table} p
                 INNER JOIN {$this->history_table} h ON p.sync_id = h.sync_id
                 WHERE h.completed_at < %s",
                $cutoff_date
            )
        );

        // Delete old history records
        $this->wpdb->delete(
            $this->history_table,
            ['completed_at < ' => $cutoff_date],
            ['%s']
        );
    }
} 