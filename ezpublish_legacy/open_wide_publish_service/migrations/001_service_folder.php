<?php

class Service_001_ServiceFolder {
 
    public function up( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'service_folder' );
        $migration->createIfNotExists( );
 
        $migration->contentobject_name = '<title>';
        $migration->description = 'Dossier qui regroupe une liste de services';
        $migration->is_container = TRUE;
        $migration->name = 'Dossier de service';
 
        $migration->addAttribute( 'title', array(
            'description' => 'Nom du dossier qui contient la liste de service',
            'is_required' => TRUE,
            'name' => 'Titre'
        ) );
 
        $migration->addToContentClassGroup( 'Service' );
        $migration->end( );
    }
 
    public function down( ) {
        $migration = new OWMigrationContentClass( );
        $migration->startMigrationOn( 'service_folder' );
        $migration->removeClass( );
    }
}