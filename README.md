# api.audioblast.org
Application Programming Interface (API) for [audioBlast](https://audioblast.org).

The public API is documented at [api.audioblast.org](https://api.audioblast.org).

## Extending functionality
The API functionality is expanded through a modular interface, exploring the [modules](https://github.com/audioblast/api.audioblast.org/tree/master/modules) directory will provide an overview of how these modules are written. 

Modules that expand functionality by providiing new analyses are likely to require the creation of new database tables (and modification of our ingest procedures), and this will require one of the admin team to assit (please raise a new GitHub issue).

Modules that solely provide new data sources may be submitted as unsolicited pull requests.

## Branches
The current web version is `release`.

## Credits
Initial development of [audioBlast](https://audioblast.org) was supported by the Leverhulme Trust funded [Automated Acoustic Observatories](https://ebaker.me.uk/aao) project at the University of York.

The project is currently hosted by the [Natural History Museum, London](https://www.nhm.ac.uk).
