<?php

namespace App;

use Illuminate\Support\Facades\Auth;

class OptionsManager
{

    public $user_id;

    public function __construct(){
    }

    public function setUser($user_id){
        $this->user_id = $user_id;
    }

    public function setValue($meta, $value)
    {
        $keys = Option::where("meta", $meta)
            ->where("user_id", $this->user_id)->get();
        if (count($keys) > 0) {
            $key = $keys->first();
            $key->value = $value;
            $key->user_id = $this->user_id;
            $key->save();
        } else {
            $key = new Option();
            $key->meta = $meta;
            $key->value = $value;
            $key->user_id = $this->user_id;
            $key->save();
        }
    }

    public function getValue($key)
    {
        $keys = Option::where("meta", $key)
            ->where("user_id", $this->user_id)->get();
        if (count($keys) > 0) {
            return $keys->first()->value;
        } else {
            return null;
        }
    }

    public function deleteValue($key)
    {
        Option::where("meta", $key)->where("user_id", $this->user_id)->delete();
    }

    public function hasValue($key)
    {
        $count = Option::where("user_id", $this->user_id)->where("meta", $key)->count();
        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function saveObject($key, $object)
    {
        $this->setValue($key, serialize($object));
    }

    public function getObject($key)
    {
        $objectData = $this->getValue($key);
        if ($objectData != null) {
            return unserialize($objectData);
        } else {
            throw new Exception("Object not exist!");
        }
    }
}