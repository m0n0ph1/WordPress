<?php

    class WP_HTML_Open_Elements
    {
        public $stack = [];

        private $has_p_in_button_scope = false;

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

        public function has_element_in_scope($tag_name)
        {
            return $this->has_element_in_specific_scope($tag_name, [

                /*
                 * Because it's not currently possible to encounter
                 * one of the termination elements, they don't need
                 * to be listed here. If they were, they would be
                 * unreachable and only waste CPU cycles while
                 * scanning through HTML.
                 */
            ]);
        }

        public function has_element_in_specific_scope($tag_name, $termination_list)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            foreach($this->walk_up() as $node)
            {
                if($node->node_name === $tag_name)
                {
                    return true;
                }

                switch($node->node_name)
                {
                    case 'HTML':
                        return false;
                }

                if(in_array($node->node_name, $termination_list, true))
                {
                    return true;
                }
            }

            return false;
        }

        public function has_element_in_list_item_scope($tag_name)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            throw new WP_HTML_Unsupported_Exception('Cannot process elements depending on list item scope.');

            return false; // The linter requires this unreachable code until the function is implemented and can return.
        }

        public function has_element_in_table_scope($tag_name)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            throw new WP_HTML_Unsupported_Exception('Cannot process elements depending on table scope.');

            return false; // The linter requires this unreachable code until the function is implemented and can return.
        }

        public function has_element_in_select_scope($tag_name)
        { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
            throw new WP_HTML_Unsupported_Exception('Cannot process elements depending on select scope.');

            return false; // The linter requires this unreachable code until the function is implemented and can return.
        }

        public function has_p_in_button_scope()
        {
            return $this->has_p_in_button_scope;
        }

        public function pop_until($tag_name)
        {
            foreach($this->walk_up() as $item)
            {
                $this->pop();

                if($tag_name === $item->node_name)
                {
                    return true;
                }
            }

            return false;
        }

        public function pop()
        {
            $item = array_pop($this->stack);

            if(null === $item)
            {
                return false;
            }

            $this->after_element_pop($item);

            return true;
        }

        public function after_element_pop($item)
        {
            /*
             * When adding support for new elements, expand this switch to trap
             * cases where the precalculated value needs to change.
             */
            switch($item->node_name)
            {
                case 'BUTTON':
                    $this->has_p_in_button_scope = $this->has_element_in_button_scope('P');
                    break;

                case 'P':
                    $this->has_p_in_button_scope = $this->has_element_in_button_scope('P');
                    break;
            }
        }

        public function has_element_in_button_scope($tag_name)
        {
            return $this->has_element_in_specific_scope($tag_name, ['BUTTON']);
        }

        public function push($stack_item)
        {
            $this->stack[] = $stack_item;
            $this->after_element_push($stack_item);
        }

        public function after_element_push($item)
        {
            /*
             * When adding support for new elements, expand this switch to trap
             * cases where the precalculated value needs to change.
             */
            switch($item->node_name)
            {
                case 'BUTTON':
                    $this->has_p_in_button_scope = false;
                    break;

                case 'P':
                    $this->has_p_in_button_scope = true;
                    break;
            }
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
                $this->after_element_pop($item);

                return true;
            }

            return false;
        }

        /*
         * Internal helpers.
         */

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
