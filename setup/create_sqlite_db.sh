#!/bin/sh 

DB='webconsole.db'
SQLITE='sqlite'
#SQLITE='sqlite3'

# create table and indexes

$SQLITE $DB <<SQL

create table demo (
    id integer not null primary key,
    filename varchar(64) not null
);

create table player (
    id integer not null primary key,
    steam_id varchar(32) not null
);

create table demo_player (
    demo_id integer not null,
    player_id integer not null,
    name varchar(32) not null
);

create unique index filename on demo ('filename');
create unique index steam_id on player ('steam_id');
create index demo_id on demo_player ('demo_id');
create index player_id on demo_player ('player_id');
create index name on demo_player ('name');

SQL

