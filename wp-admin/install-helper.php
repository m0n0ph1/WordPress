<?php

    require_once dirname(__DIR__).'/wp-load.php';

    if(! function_exists('maybe_create_table')) :

        function maybe_create_table($table_name, $create_ddl)
        {
            global $wpdb;

            foreach($wpdb->get_col('SHOW TABLES', 0) as $table)
            {
                if($table === $table_name)
                {
                    return true;
                }
            }

            // Didn't find it, so try to create it.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No applicable variables for this query.
            $wpdb->query($create_ddl);

            // We cannot directly tell whether this succeeded!
            foreach($wpdb->get_col('SHOW TABLES', 0) as $table)
            {
                if($table === $table_name)
                {
                    return true;
                }
            }

            return false;
        }
    endif;

    if(! function_exists('maybe_add_column')) :

        function maybe_add_column($table_name, $column_name, $create_ddl)
        {
            global $wpdb;

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
            foreach($wpdb->get_col("DESC $table_name", 0) as $column)
            {
                if($column === $column_name)
                {
                    return true;
                }
            }

            // Didn't find it, so try to create it.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No applicable variables for this query.
            $wpdb->query($create_ddl);

            // We cannot directly tell whether this succeeded!
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
            foreach($wpdb->get_col("DESC $table_name", 0) as $column)
            {
                if($column === $column_name)
                {
                    return true;
                }
            }

            return false;
        }
    endif;

    function maybe_drop_column($table_name, $column_name, $drop_ddl)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
        foreach($wpdb->get_col("DESC $table_name", 0) as $column)
        {
            if($column === $column_name)
            {
                // Found it, so try to drop it.
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- No applicable variables for this query.
                $wpdb->query($drop_ddl);

                // We cannot directly tell whether this succeeded!
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
                foreach($wpdb->get_col("DESC $table_name", 0) as $column)
                {
                    if($column === $column_name)
                    {
                        return false;
                    }
                }
            }
        }

        // Else didn't find it.
        return true;
    }

    function check_column(
        $table_name, $col_name, $col_type, $is_null = null, $key = null, $default_value = null, $extra = null
    ) {
        global $wpdb;

        $diffs = 0;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Cannot be prepared. Fetches columns for table names.
        $results = $wpdb->get_results("DESC $table_name");

        foreach($results as $row)
        {
            if($row->Field === $col_name)
            {
                // Got our column, check the params.
                if((null !== $col_type) && ($row->Type !== $col_type))
                {
                    ++$diffs;
                }
                if((null !== $is_null) && ($row->Null !== $is_null))
                {
                    ++$diffs;
                }
                if((null !== $key) && ($row->Key !== $key))
                {
                    ++$diffs;
                }
                if((null !== $default_value) && ($row->Default !== $default_value))
                {
                    ++$diffs;
                }
                if((null !== $extra) && ($row->Extra !== $extra))
                {
                    ++$diffs;
                }

                return $diffs <= 0;
            } // End if found our column.
        }

        return false;
    }
