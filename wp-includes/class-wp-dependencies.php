<?php

    #[AllowDynamicProperties]
    class WP_Dependencies
    {
        public $registered = [];

        public $queue = [];

        public $to_do = [];

        public $done = [];

        public $args = [];

        public $groups = [];

        public $group = 0;

        private $all_queued_deps;

        private $queued_before_register = [];

        public function do_items($handles = false, $group = false)
        {
            /*
             * If nothing is passed, print the queue. If a string is passed,
             * print that item. If an array is passed, print those items.
             */
            $handles = false === $handles ? $this->queue : (array) $handles;
            $this->all_deps($handles);

            foreach($this->to_do as $key => $handle)
            {
                if(! in_array($handle, $this->done, true) && isset($this->registered[$handle]))
                {
                    /*
                     * Attempt to process the item. If successful,
                     * add the handle to the done array.
                     *
                     * Unset the item from the to_do array.
                     */
                    if($this->do_item($handle, $group))
                    {
                        $this->done[] = $handle;
                    }

                    unset($this->to_do[$key]);
                }
            }

            return $this->done;
        }

        public function all_deps($handles, $recursion = false, $group = false)
        {
            $handles = (array) $handles;
            if(! $handles)
            {
                return false;
            }

            foreach($handles as $handle)
            {
                $handle_parts = explode('?', $handle);
                $handle = $handle_parts[0];
                $queued = in_array($handle, $this->to_do, true);

                if(in_array($handle, $this->done, true))
                { // Already done.
                    continue;
                }

                $moved = $this->set_group($handle, $recursion, $group);
                $new_group = $this->groups[$handle];

                if($queued && ! $moved)
                { // Already queued and in the right group.
                    continue;
                }

                $keep_going = true;
                if(! isset($this->registered[$handle]))
                {
                    $keep_going = false; // Item doesn't exist.
                }
                elseif($this->registered[$handle]->deps && array_diff($this->registered[$handle]->deps, array_keys($this->registered)))
                {
                    $keep_going = false; // Item requires dependencies that don't exist.
                }
                elseif($this->registered[$handle]->deps && ! $this->all_deps($this->registered[$handle]->deps, true, $new_group))
                {
                    $keep_going = false; // Item requires dependencies that don't exist.
                }

                if(! $keep_going)
                { // Either item or its dependencies don't exist.
                    if($recursion)
                    {
                        return false; // Abort this branch.
                    }
                    else
                    {
                        continue; // We're at the top level. Move on to the next one.
                    }
                }

                if($queued)
                { // Already grabbed it and its dependencies.
                    continue;
                }

                if(isset($handle_parts[1]))
                {
                    $this->args[$handle] = $handle_parts[1];
                }

                $this->to_do[] = $handle;
            }

            return true;
        }

        public function set_group($handle, $recursion, $group)
        {
            $group = (int) $group;

            if(isset($this->groups[$handle]) && $this->groups[$handle] <= $group)
            {
                return false;
            }

            $this->groups[$handle] = $group;

            return true;
        }

        public function do_item($handle, $group = false)
        {
            return isset($this->registered[$handle]);
        }

        public function add($handle, $src, $deps = [], $ver = false, $args = null)
        {
            if(isset($this->registered[$handle]))
            {
                return false;
            }
            $this->registered[$handle] = new _WP_Dependency($handle, $src, $deps, $ver, $args);

            // If the item was enqueued before the details were registered, enqueue it now.
            if(array_key_exists($handle, $this->queued_before_register))
            {
                if(! is_null($this->queued_before_register[$handle]))
                {
                    $this->enqueue($handle.'?'.$this->queued_before_register[$handle]);
                }
                else
                {
                    $this->enqueue($handle);
                }

                unset($this->queued_before_register[$handle]);
            }

            return true;
        }

        public function enqueue($handles)
        {
            foreach((array) $handles as $handle)
            {
                $handle = explode('?', $handle);

                if(! in_array($handle[0], $this->queue, true) && isset($this->registered[$handle[0]]))
                {
                    $this->queue[] = $handle[0];

                    // Reset all dependencies so they must be recalculated in recurse_deps().
                    $this->all_queued_deps = null;

                    if(isset($handle[1]))
                    {
                        $this->args[$handle[0]] = $handle[1];
                    }
                }
                elseif(! isset($this->registered[$handle[0]]))
                {
                    $this->queued_before_register[$handle[0]] = null; // $args

                    if(isset($handle[1]))
                    {
                        $this->queued_before_register[$handle[0]] = $handle[1];
                    }
                }
            }
        }

        public function add_data($handle, $key, $value)
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            return $this->registered[$handle]->add_data($key, $value);
        }

        public function get_data($handle, $key)
        {
            if(! isset($this->registered[$handle]))
            {
                return false;
            }

            if(! isset($this->registered[$handle]->extra[$key]))
            {
                return false;
            }

            return $this->registered[$handle]->extra[$key];
        }

        public function remove($handles)
        {
            foreach((array) $handles as $handle)
            {
                unset($this->registered[$handle]);
            }
        }

        public function dequeue($handles)
        {
            foreach((array) $handles as $handle)
            {
                $handle = explode('?', $handle);
                $key = array_search($handle[0], $this->queue, true);

                if(false !== $key)
                {
                    // Reset all dependencies so they must be recalculated in recurse_deps().
                    $this->all_queued_deps = null;

                    unset($this->queue[$key]);
                    unset($this->args[$handle[0]]);
                }
                elseif(array_key_exists($handle[0], $this->queued_before_register))
                {
                    unset($this->queued_before_register[$handle[0]]);
                }
            }
        }

        public function query($handle, $status = 'registered')
        {
            switch($status)
            {
                case 'registered':
                case 'scripts': // Back compat.
                    if(isset($this->registered[$handle]))
                    {
                        return $this->registered[$handle];
                    }

                    return false;

                case 'enqueued':
                case 'queue': // Back compat.
                    if(in_array($handle, $this->queue, true))
                    {
                        return true;
                    }

                    return $this->recurse_deps($this->queue, $handle);

                case 'to_do':
                case 'to_print': // Back compat.
                    return in_array($handle, $this->to_do, true);

                case 'done':
                case 'printed': // Back compat.
                    return in_array($handle, $this->done, true);
            }

            return false;
        }

        protected function recurse_deps($queue, $handle)
        {
            if(isset($this->all_queued_deps))
            {
                return isset($this->all_queued_deps[$handle]);
            }

            $all_deps = array_fill_keys($queue, true);
            $queues = [];
            $done = [];

            while($queue)
            {
                foreach($queue as $queued)
                {
                    if(! isset($done[$queued]) && isset($this->registered[$queued]))
                    {
                        $deps = $this->registered[$queued]->deps;
                        if($deps)
                        {
                            $all_deps += array_fill_keys($deps, true);
                            array_push($queues, $deps);
                        }
                        $done[$queued] = true;
                    }
                }
                $queue = array_pop($queues);
            }

            $this->all_queued_deps = $all_deps;

            return isset($this->all_queued_deps[$handle]);
        }
    }
