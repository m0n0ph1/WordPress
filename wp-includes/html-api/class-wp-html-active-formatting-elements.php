<?php

    class WP_HTML_Active_Formatting_Elements
    {
        private $stack = [];

        public function contains_node($token)
        {
            foreach($this->walk_up() as $item)
            {
                if($token->bookmark_name === $item->bookmark_name)
                {
                    return true;
                }
            }

            return false;
        }

        public function walk_up()
        {
            for($i = count($this->stack) - 1; $i >= 0; $i--)
            {
                yield $this->stack[$i];
            }
        }

        public function current_node()
        {
            $current_node = end($this->stack);

            return $current_node ? $current_node : null;
        }

        public function push($token)
        {
            /*
             * > If there are already three elements in the list of active formatting elements after the last marker,
             * > if any, or anywhere in the list if there are no markers, that have the same tag name, namespace, and
             * > attributes as element, then remove the earliest such element from the list of active formatting
             * > elements. For these purposes, the attributes must be compared as they were when the elements were
             * > created by the parser; two elements have the same attributes if all their parsed attributes can be
             * > paired such that the two attributes in each pair have identical names, namespaces, and values
             * > (the order of the attributes does not matter).
             *
             * @TODO: Implement the "Noah's Ark clause" to only add up to three of any given kind of formatting elements to the stack.
             */
            // > Add element to the list of active formatting elements.
            $this->stack[] = $token;
        }

        public function remove_node($token)
        {
            foreach($this->walk_up() as $position_from_end => $item)
            {
                if($token->bookmark_name !== $item->bookmark_name)
                {
                    continue;
                }

                $position_from_start = $this->count() - $position_from_end - 1;
                array_splice($this->stack, $position_from_start, 1);

                return true;
            }

            return false;
        }

        public function count()
        {
            return count($this->stack);
        }

        public function walk_down()
        {
            $count = count($this->stack);

            for($i = 0; $i < $count; $i++)
            {
                yield $this->stack[$i];
            }
        }
    }
