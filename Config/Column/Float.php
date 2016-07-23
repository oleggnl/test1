<?php

class Config_Column_Float extends Config_Column_Integer {

    /**
     *
     * {@inheritDoc}
     * @see Config_Column_Integer::getBindParameterPrefix()
     */
    public function getBindParameterPrefix() {
        return 'd';
    }
}