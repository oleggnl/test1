<?php

class Config_Column_Date extends Config_Column_DateTime {
    protected function getStringRepresentation() {
        return $this->dateTime->format('Y-m-d');
    }
}