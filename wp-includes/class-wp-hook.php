<?php

    #[AllowDynamicProperties]
    final class WP_Hook implements Iterator, ArrayAccess
    {
        public $callbacks = [];

        private $iterations = [];

        private $current_priority = [];

        private $nesting_level = 0;

        private $doing_action = false;

        public static function build_preinitialized_hooks($filters)
        {
            $normalized = [];

            foreach($filters as $hook_name => $callback_groups)
            {
                if($callback_groups instanceof WP_Hook)
                {
                    $normalized[$hook_name] = $callback_groups;
                    continue;
                }

                $hook = new WP_Hook();

                // Loop through callback groups.
                foreach($callback_groups as $priority => $callbacks)
                {
                    // Loop through callbacks.
                    foreach($callbacks as $cb)
                    {
                        $hook->add_filter($hook_name, $cb['function'], $priority, $cb['accepted_args']);
                    }
                }

                $normalized[$hook_name] = $hook;
            }

            return $normalized;
        }

        public function add_filter($hook_name, $callback, $priority, $accepted_args)
        {
            $idx = _wp_filter_build_unique_id($hook_name, $callback, $priority);

            $priority_existed = isset($this->callbacks[$priority]);

            $this->callbacks[$priority][$idx] = [
                'function' => $callback,
                'accepted_args' => (int) $accepted_args,
            ];

            // If we're adding a new priority to the list, put them back in sorted order.
            if(! $priority_existed && count($this->callbacks) > 1)
            {
                ksort($this->callbacks, SORT_NUMERIC);
            }

            if($this->nesting_level > 0)
            {
                $this->resort_active_iterations($priority, $priority_existed);
            }
        }

        private function resort_active_iterations($new_priority = false, $priority_existed = false)
        {
            $new_priorities = array_keys($this->callbacks);

            // If there are no remaining hooks, clear out all running iterations.
            if(! $new_priorities)
            {
                foreach($this->iterations as $index => $iteration)
                {
                    $this->iterations[$index] = $new_priorities;
                }

                return;
            }

            $min = min($new_priorities);

            foreach($this->iterations as $index => &$iteration)
            {
                $current = current($iteration);

                // If we're already at the end of this iteration, just leave the array pointer where it is.
                if(false === $current)
                {
                    continue;
                }

                $iteration = $new_priorities;

                if($current < $min)
                {
                    array_unshift($iteration, $current);
                    continue;
                }

                while(current($iteration) < $current)
                {
                    if(false === next($iteration))
                    {
                        break;
                    }
                }

                // If we have a new priority that didn't exist, but ::apply_filters() or ::do_action() thinks it's the current priority...
                if($new_priority === $this->current_priority[$index] && ! $priority_existed)
                {
                    /*
                     * ...and the new priority is the same as what $this->iterations thinks is the previous
                     * priority, we need to move back to it.
                     */

                    if(false !== current($iteration))
                    {
                        // Otherwise, just go back to the previous element.
                        $prev = prev($iteration);
                    }
                    else
                    {
                        // If we've already moved off the end of the array, go back to the last element.
                        $prev = end($iteration);
                    }

                    if(false === $prev)
                    {
                        // Start of the array. Reset, and go about our day.
                        reset($iteration);
                    }
                    elseif($new_priority !== $prev)
                    {
                        // Previous wasn't the same. Move forward again.
                        next($iteration);
                    }
                }
            }

            unset($iteration);
        }

        public function remove_filter($hook_name, $callback, $priority)
        {
            $function_key = _wp_filter_build_unique_id($hook_name, $callback, $priority);

            $exists = isset($this->callbacks[$priority][$function_key]);

            if($exists)
            {
                unset($this->callbacks[$priority][$function_key]);

                if(! $this->callbacks[$priority])
                {
                    unset($this->callbacks[$priority]);

                    if($this->nesting_level > 0)
                    {
                        $this->resort_active_iterations();
                    }
                }
            }

            return $exists;
        }

        public function has_filter($hook_name = '', $callback = false)
        {
            if(false === $callback)
            {
                return $this->has_filters();
            }

            $function_key = _wp_filter_build_unique_id($hook_name, $callback, false);

            if(! $function_key)
            {
                return false;
            }

            foreach($this->callbacks as $priority => $callbacks)
            {
                if(isset($callbacks[$function_key]))
                {
                    return $priority;
                }
            }

            return false;
        }

        public function has_filters()
        {
            foreach($this->callbacks as $callbacks)
            {
                if($callbacks)
                {
                    return true;
                }
            }

            return false;
        }

        public function remove_all_filters($priority = false)
        {
            if(! $this->callbacks)
            {
                return;
            }

            if(false === $priority)
            {
                $this->callbacks = [];
            }
            elseif(isset($this->callbacks[$priority]))
            {
                unset($this->callbacks[$priority]);
            }

            if($this->nesting_level > 0)
            {
                $this->resort_active_iterations();
            }
        }

        public function do_action($args)
        {
            $this->doing_action = true;
            $this->apply_filters('', $args);

            // If there are recursive calls to the current action, we haven't finished it until we get to the last one.
            if(! $this->nesting_level)
            {
                $this->doing_action = false;
            }
        }

        public function apply_filters($value, $args)
        {
            if(! $this->callbacks)
            {
                return $value;
            }

            $nesting_level = $this->nesting_level++;

            $this->iterations[$nesting_level] = array_keys($this->callbacks);

            $num_args = count($args);

            do
            {
                $this->current_priority[$nesting_level] = current($this->iterations[$nesting_level]);

                $priority = $this->current_priority[$nesting_level];

                foreach($this->callbacks[$priority] as $the_)
                {
                    if(! $this->doing_action)
                    {
                        $args[0] = $value;
                    }

                    // Avoid the array_slice() if possible.
                    if(0 === $the_['accepted_args'])
                    {
                        $value = call_user_func($the_['function']);
                    }
                    elseif($the_['accepted_args'] >= $num_args)
                    {
                        $value = call_user_func_array($the_['function'], $args);
                    }
                    else
                    {
                        $value = call_user_func_array($the_['function'], array_slice($args, 0, $the_['accepted_args']));
                    }
                }
            }
            while(false !== next($this->iterations[$nesting_level]));

            unset($this->iterations[$nesting_level]);
            unset($this->current_priority[$nesting_level]);

            --$this->nesting_level;

            return $value;
        }

        public function do_all_hook(&$args)
        {
            $nesting_level = $this->nesting_level++;
            $this->iterations[$nesting_level] = array_keys($this->callbacks);

            do
            {
                $priority = current($this->iterations[$nesting_level]);

                foreach($this->callbacks[$priority] as $the_)
                {
                    call_user_func_array($the_['function'], $args);
                }
            }
            while(false !== next($this->iterations[$nesting_level]));

            unset($this->iterations[$nesting_level]);
            --$this->nesting_level;
        }

        public function current_priority()
        {
            if(false === current($this->iterations))
            {
                return false;
            }

            return current(current($this->iterations));
        }

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            return isset($this->callbacks[$offset]);
        }

        #[ReturnTypeWillChange]
        public function offsetGet($offset)
        {
            if(isset($this->callbacks[$offset]))
            {
                return $this->callbacks[$offset];
            }

            return null;
        }

        #[ReturnTypeWillChange]
        public function offsetSet($offset, $value)
        {
            if(is_null($offset))
            {
                $this->callbacks[] = $value;
            }
            else
            {
                $this->callbacks[$offset] = $value;
            }
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            unset($this->callbacks[$offset]);
        }

        #[ReturnTypeWillChange]
        public function current()
        {
            return current($this->callbacks);
        }

        #[ReturnTypeWillChange]
        public function next()
        {
            return next($this->callbacks);
        }

        #[ReturnTypeWillChange]
        public function key()
        {
            return key($this->callbacks);
        }

        #[ReturnTypeWillChange]
        public function valid()
        {
            return key($this->callbacks) !== null;
        }

        #[ReturnTypeWillChange]
        public function rewind()
        {
            reset($this->callbacks);
        }
    }
