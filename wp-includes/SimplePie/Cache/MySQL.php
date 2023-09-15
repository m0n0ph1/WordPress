<?php

    class MySQL extends SimplePie_Cache_DB
    {
        protected $mysql;

        protected $options;

        protected $id;

        public function __construct($location, $name, $type)
        {
            $this->options = [
                'user' => null,
                'pass' => null,
                'host' => '127.0.0.1',
                'port' => '3306',
                'path' => '',
                'extras' => [
                    'prefix' => '',
                    'cache_purge_time' => 2592000
                ],
            ];

            $this->options = SimplePie_Misc::array_merge_recursive($this->options, SimplePie_Cache::parse_URL($location));

            // Path is prefixed with a "/"
            $this->options['dbname'] = substr($this->options['path'], 1);

            try
            {
                $this->mysql = new PDO("mysql:dbname={$this->options['dbname']};host={$this->options['host']};port={$this->options['port']}", $this->options['user'], $this->options['pass'], [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']);
            }
            catch(PDOException $e)
            {
                $this->mysql = null;

                return;
            }

            $this->id = $name.$type;

            if(! $query = $this->mysql->query('SHOW TABLES'))
            {
                $this->mysql = null;

                return;
            }

            $db = [];
            while($row = $query->fetchColumn())
            {
                $db[] = $row;
            }

            if(! in_array($this->options['extras']['prefix'].'cache_data', $db))
            {
                $query = $this->mysql->exec('CREATE TABLE `'.$this->options['extras']['prefix'].'cache_data` (`id` TEXT CHARACTER SET utf8 NOT NULL, `items` SMALLINT NOT NULL DEFAULT 0, `data` BLOB NOT NULL, `mtime` INT UNSIGNED NOT NULL, UNIQUE (`id`(125)))');
                if($query === false)
                {
                    trigger_error("Can't create ".$this->options['extras']['prefix']."cache_data table, check permissions", E_USER_WARNING);
                    $this->mysql = null;

                    return;
                }
            }

            if(! in_array($this->options['extras']['prefix'].'items', $db))
            {
                $query = $this->mysql->exec('CREATE TABLE `'.$this->options['extras']['prefix'].'items` (`feed_id` TEXT CHARACTER SET utf8 NOT NULL, `id` TEXT CHARACTER SET utf8 NOT NULL, `data` MEDIUMBLOB NOT NULL, `posted` INT UNSIGNED NOT NULL, INDEX `feed_id` (`feed_id`(125)))');
                if($query === false)
                {
                    trigger_error("Can't create ".$this->options['extras']['prefix']."items table, check permissions", E_USER_WARNING);
                    $this->mysql = null;

                    return;
                }
            }
        }

        public function save($data)
        {
            if($this->mysql === null)
            {
                return false;
            }

            $query = $this->mysql->prepare('DELETE i, cd FROM `'.$this->options['extras']['prefix'].'cache_data` cd, '.'`'.$this->options['extras']['prefix'].'items` i '.'WHERE cd.id = i.feed_id '.'AND cd.mtime < (unix_timestamp() - :purge_time)');
            $query->bindValue(':purge_time', $this->options['extras']['cache_purge_time']);

            if(! $query->execute())
            {
                return false;
            }

            if($data instanceof SimplePie)
            {
                $data = clone $data;

                $prepared = self::prepare_simplepie_object_for_cache($data);

                $query = $this->mysql->prepare('SELECT COUNT(*) FROM `'.$this->options['extras']['prefix'].'cache_data` WHERE `id` = :feed');
                $query->bindValue(':feed', $this->id);
                if($query->execute())
                {
                    if($query->fetchColumn() > 0)
                    {
                        $items = count($prepared[1]);
                        if($items)
                        {
                            $sql = 'UPDATE `'.$this->options['extras']['prefix'].'cache_data` SET `items` = :items, `data` = :data, `mtime` = :time WHERE `id` = :feed';
                            $query = $this->mysql->prepare($sql);
                            $query->bindValue(':items', $items);
                        }
                        else
                        {
                            $sql = 'UPDATE `'.$this->options['extras']['prefix'].'cache_data` SET `data` = :data, `mtime` = :time WHERE `id` = :feed';
                            $query = $this->mysql->prepare($sql);
                        }

                        $query->bindValue(':data', $prepared[0]);
                        $query->bindValue(':time', time());
                        $query->bindValue(':feed', $this->id);
                        if(! $query->execute())
                        {
                            return false;
                        }
                    }
                    else
                    {
                        $query = $this->mysql->prepare('INSERT INTO `'.$this->options['extras']['prefix'].'cache_data` (`id`, `items`, `data`, `mtime`) VALUES(:feed, :count, :data, :time)');
                        $query->bindValue(':feed', $this->id);
                        $query->bindValue(':count', count($prepared[1]));
                        $query->bindValue(':data', $prepared[0]);
                        $query->bindValue(':time', time());
                        if(! $query->execute())
                        {
                            return false;
                        }
                    }

                    $ids = array_keys($prepared[1]);
                    if(! empty($ids))
                    {
                        foreach($ids as $id)
                        {
                            $database_ids[] = $this->mysql->quote($id);
                        }

                        $query = $this->mysql->prepare('SELECT `id` FROM `'.$this->options['extras']['prefix'].'items` WHERE `id` = '.implode(' OR `id` = ', $database_ids).' AND `feed_id` = :feed');
                        $query->bindValue(':feed', $this->id);

                        if($query->execute())
                        {
                            $existing_ids = [];
                            while($row = $query->fetchColumn())
                            {
                                $existing_ids[] = $row;
                            }

                            $new_ids = array_diff($ids, $existing_ids);

                            foreach($new_ids as $new_id)
                            {
                                if(! ($date = $prepared[1][$new_id]->get_date('U')))
                                {
                                    $date = time();
                                }

                                $query = $this->mysql->prepare('INSERT INTO `'.$this->options['extras']['prefix'].'items` (`feed_id`, `id`, `data`, `posted`) VALUES(:feed, :id, :data, :date)');
                                $query->bindValue(':feed', $this->id);
                                $query->bindValue(':id', $new_id);
                                $query->bindValue(':data', serialize($prepared[1][$new_id]->data));
                                $query->bindValue(':date', $date);
                                if(! $query->execute())
                                {
                                    return false;
                                }
                            }

                            return true;
                        }
                    }
                    else
                    {
                        return true;
                    }
                }
            }
            else
            {
                $query = $this->mysql->prepare('SELECT `id` FROM `'.$this->options['extras']['prefix'].'cache_data` WHERE `id` = :feed');
                $query->bindValue(':feed', $this->id);
                if($query->execute())
                {
                    if($query->rowCount() > 0)
                    {
                        $query = $this->mysql->prepare('UPDATE `'.$this->options['extras']['prefix'].'cache_data` SET `items` = 0, `data` = :data, `mtime` = :time WHERE `id` = :feed');
                        $query->bindValue(':data', serialize($data));
                        $query->bindValue(':time', time());
                        $query->bindValue(':feed', $this->id);
                        if($this->execute())
                        {
                            return true;
                        }
                    }
                    else
                    {
                        $query = $this->mysql->prepare('INSERT INTO `'.$this->options['extras']['prefix'].'cache_data` (`id`, `items`, `data`, `mtime`) VALUES(:id, 0, :data, :time)');
                        $query->bindValue(':id', $this->id);
                        $query->bindValue(':data', serialize($data));
                        $query->bindValue(':time', time());
                        if($query->execute())
                        {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        public function load()
        {
            if($this->mysql === null)
            {
                return false;
            }

            $query = $this->mysql->prepare('SELECT `items`, `data` FROM `'.$this->options['extras']['prefix'].'cache_data` WHERE `id` = :id');
            $query->bindValue(':id', $this->id);
            if($query->execute() && ($row = $query->fetch()))
            {
                $data = unserialize($row[1]);

                if(isset($this->options['items'][0]))
                {
                    $items = (int) $this->options['items'][0];
                }
                else
                {
                    $items = (int) $row[0];
                }

                if($items !== 0)
                {
                    if(isset($data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0]))
                    {
                        $feed =& $data['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['feed'][0];
                    }
                    elseif(isset($data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0]))
                    {
                        $feed =& $data['child'][SIMPLEPIE_NAMESPACE_ATOM_03]['feed'][0];
                    }
                    elseif(isset($data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0]))
                    {
                        $feed =& $data['child'][SIMPLEPIE_NAMESPACE_RDF]['RDF'][0];
                    }
                    elseif(isset($data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0]))
                    {
                        $feed =& $data['child'][SIMPLEPIE_NAMESPACE_RSS_20]['rss'][0];
                    }
                    else
                    {
                        $feed = null;
                    }

                    if($feed !== null)
                    {
                        $sql = 'SELECT `data` FROM `'.$this->options['extras']['prefix'].'items` WHERE `feed_id` = :feed ORDER BY `posted` DESC';
                        if($items > 0)
                        {
                            $sql .= ' LIMIT '.$items;
                        }

                        $query = $this->mysql->prepare($sql);
                        $query->bindValue(':feed', $this->id);
                        if($query->execute())
                        {
                            while($row = $query->fetchColumn())
                            {
                                $feed['child'][SIMPLEPIE_NAMESPACE_ATOM_10]['entry'][] = unserialize($row);
                            }
                        }
                        else
                        {
                            return false;
                        }
                    }
                }

                return $data;
            }

            return false;
        }

        public function mtime()
        {
            if($this->mysql === null)
            {
                return false;
            }

            $query = $this->mysql->prepare('SELECT `mtime` FROM `'.$this->options['extras']['prefix'].'cache_data` WHERE `id` = :id');
            $query->bindValue(':id', $this->id);
            if($query->execute() && ($time = $query->fetchColumn()))
            {
                return $time;
            }

            return false;
        }

        public function touch()
        {
            if($this->mysql === null)
            {
                return false;
            }

            $query = $this->mysql->prepare('UPDATE `'.$this->options['extras']['prefix'].'cache_data` SET `mtime` = :time WHERE `id` = :id');
            $query->bindValue(':time', time());
            $query->bindValue(':id', $this->id);

            return $query->execute() && $query->rowCount() > 0;
        }

        public function unlink()
        {
            if($this->mysql === null)
            {
                return false;
            }

            $query = $this->mysql->prepare('DELETE FROM `'.$this->options['extras']['prefix'].'cache_data` WHERE `id` = :id');
            $query->bindValue(':id', $this->id);
            $query2 = $this->mysql->prepare('DELETE FROM `'.$this->options['extras']['prefix'].'items` WHERE `feed_id` = :id');
            $query2->bindValue(':id', $this->id);

            return $query->execute() && $query2->execute();
        }
    }
