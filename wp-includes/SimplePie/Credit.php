<?php

    class SimplePie_Credit
    {
        var $role;

        var $scheme;

        var $name;

        public function __construct($role = null, $scheme = null, $name = null)
        {
            $this->role = $role;
            $this->scheme = $scheme;
            $this->name = $name;
        }

        public function __toString()
        {
            // There is no $this->data here
            return md5(serialize($this));
        }

        public function get_role()
        {
            if($this->role !== null)
            {
                return $this->role;
            }

            return null;
        }

        public function get_scheme()
        {
            if($this->scheme !== null)
            {
                return $this->scheme;
            }

            return null;
        }

        public function get_name()
        {
            if($this->name !== null)
            {
                return $this->name;
            }

            return null;
        }
    }
