<?php

class Config_Column_Time extends Config_Column_DateTime {
    /**
     *
     * {@inheritDoc}
     * @see Config_Column_DateTime::getStringRepresentation()
     */
    protected function getStringRepresentation() {
        return sprintf('%d:%d:%d', rand(-838, 838), rand(0, 59), rand(0, 59));
    }
}