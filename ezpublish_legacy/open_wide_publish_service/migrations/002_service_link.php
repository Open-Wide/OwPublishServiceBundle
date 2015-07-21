<?php

class Service_002_ServiceLink {
 
    public function up( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'service_link' );
        $migration->createIfNotExists( );
 
        $migration->contentobject_name = '<title>';
        $migration->description = 'Descriptif du service';
        $migration->name = 'Service';
 
        $migration->addAttribute( 'title', array(
            'is_required' => TRUE,
            'name' => 'Titre'
        ) );
        $migration->addAttribute( 'short_title', array(
            'name' => 'Titre court'
        ) );
        $migration->addAttribute( 'code', array(
            'description' => 'Le code ne doit comporter ni espace, ni caracères spéciaux. Uniquement lettres minuscules et chiffres.',
            'is_required' => TRUE,
            'name' => 'Code service'
        ) );
        $migration->addAttribute( 'presentation', array(
            'data_type_string' => 'eztext',
            'name' => 'Présentation du service'
        ) );
        $migration->addAttribute( 'logo', array(
            'data_type_string' => 'ezobjectrelation',
            'description' => 'Image qui représente le service',
            'is_required' => TRUE,
            'name' => 'Logo',
            'selection_method' => 'Browse',
            'fuzzy_match' => FALSE
        ) );
        $migration->addAttribute( 'url', array(
            'data_type_string' => 'ezurl',
            'description' => 'EXemple : http://www.mon-service.com',
            'is_required' => TRUE,
            'is_searchable' => FALSE,
            'name' => 'Url du service'
        ) );
        $migration->addAttribute( 'category', array(
            'data_type_string' => 'ezselection',
            'name' => 'Catégorie de service',
            'data_int1' => 1,
            'data_text5' => '<?xml version="1.0" encoding="utf-8"?>
<ezselection><options><option id="0" name="Catégorie 1"/><option id="1" name="Catégorie 2"/><option id="2" name="Catégorie 3"/><option id="3" name="Catégorie 4"/><option id="4" name="Catégorie 5"/><option id="5" name="Catégorie 6"/></options></ezselection>
'
        ) );
 
        $migration->addToContentClassGroup( 'Service' );
        $migration->end( );
    }
 
    public function down( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'service_link' );
        $migration->removeClass( );
    }
}