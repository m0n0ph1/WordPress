<?php

    class Author
    {
        public $name;

        public $link;

        public $email;

        public function __construct($name = null, $link = null, $email = null)
        {
            $this->name = $name;
            $this->link = $link;
            $this->email = $email;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_name()
        {
            if($this->name !== null)
            {
                return $this->name;
            }

            return null;
        }

        public function get_link()
        {
            if($this->link !== null)
            {
                return $this->link;
            }

            return null;
        }

        public function get_email()
        {
            if($this->email !== null)
            {
                return $this->email;
            }

            return null;
        }
    }
