<?php

    class IXR_Value
    {
        public $data;

        public $type;

        public function IXR_Value($data, $type = false)
        {
            $this->__construct($data, $type);
        }

        public function __construct($data, $type = false)
        {
            $this->data = $data;
            if(! $type)
            {
                $type = $this->calculateType();
            }
            $this->type = $type;
            if($type == 'struct')
            {
                // Turn all the values in the array in to new IXR_Value objects
                foreach($this->data as $key => $value)
                {
                    $this->data[$key] = new IXR_Value($value);
                }
            }
            if($type == 'array')
            {
                foreach($this->data as $i => $iValue)
                {
                    $this->data[$i] = new IXR_Value($this->data[$i]);
                }
            }
        }

        public function calculateType()
        {
            if($this->data === true || $this->data === false)
            {
                return 'boolean';
            }
            if(is_int($this->data))
            {
                return 'int';
            }
            if(is_float($this->data))
            {
                return 'double';
            }

            // Deal with IXR object types base64 and date
            if(is_object($this->data) && is_a($this->data, 'IXR_Date'))
            {
                return 'date';
            }
            if(is_object($this->data) && is_a($this->data, 'IXR_Base64'))
            {
                return 'base64';
            }

            // If it is a normal PHP object convert it in to a struct
            if(is_object($this->data))
            {
                $this->data = get_object_vars($this->data);

                return 'struct';
            }
            if(! is_array($this->data))
            {
                return 'string';
            }

            // We have an array - is it an array or a struct?
            if($this->isStruct($this->data))
            {
                return 'struct';
            }
            else
            {
                return 'array';
            }
        }

        public function isStruct($array)
        {
            $expected = 0;
            foreach($array as $key => $value)
            {
                if((string) $key !== (string) $expected)
                {
                    return true;
                }
                $expected++;
            }

            return false;
        }

        public function getXml()
        {
            // Return XML for this value
            switch($this->type)
            {
                case 'boolean':
                    return '<boolean>'.(($this->data) ? '1' : '0').'</boolean>';
                    break;
                case 'int':
                    return '<int>'.$this->data.'</int>';
                    break;
                case 'double':
                    return '<double>'.$this->data.'</double>';
                    break;
                case 'string':
                    return '<string>'.htmlspecialchars($this->data).'</string>';
                    break;
                case 'array':
                    $return = '<array><data>'."\n";
                    foreach($this->data as $item)
                    {
                        $return .= '  <value>'.$item->getXml()."</value>\n";
                    }
                    $return .= '</data></array>';

                    return $return;
                    break;
                case 'struct':
                    $return = '<struct>'."\n";
                    foreach($this->data as $name => $value)
                    {
                        $name = htmlspecialchars($name);
                        $return .= "  <member><name>$name</name><value>";
                        $return .= $value->getXml()."</value></member>\n";
                    }
                    $return .= '</struct>';

                    return $return;
                    break;
                case 'date':
                case 'base64':
                    return $this->data->getXml();
                    break;
            }

            return false;
        }
    }
