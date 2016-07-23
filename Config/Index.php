<?php
class Config_Index {
    public $nonUnique;
    public $keyName;
    public $seqInIndex;
    public $columnName;
    public $collation;
    public $cardonality;
    public $subPart;
    public $packed;
    public $nullable;
    public $indexType;
    public $comment;
    public $indexComment;

    public function __construct(array $config) {
        $this->nonUnique    = $config['Non_unique'];
        $this->keyName      = $config['Key_name'];
        $this->seqInIndex   = $config['Seq_in_index'];
        $this->columnName   = $config['Column_name'];
        $this->collation    = $config['Collation'];
        $this->cardonality  = $config['Cardinality'];
        $this->subPart      = $config['Sub_part'];
        $this->packed       = $config['Packed'];
        $this->nullable     = $config['Null'];
        $this->indexType    = $config['Index_type'];
        $this->comment      = $config['Comment'];
        $this->indexComment = $config['Index_comment'];
    }

}