<?php

class AlertStatus extends Application
{
    public $id;
    public $alert_id;
    public $user_id;
    public $read_date;

    public static $table = "alerts_status";
    public static $id_field = "id";

    public static function create($params)
    {
        $alert = new self();
        $alert->alert_id = $params['id'];
        $alert->user_id = $params['user'];
        $alert->read_date = date('Y-m-d H:i:s');
        $alert->save();
    }
}
