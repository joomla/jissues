## Documentation

The developer documentation is written in markdown syntax in plain text documents, managed in a git repository and ready for your contribution:

https://github.com/joomla/jissues/tree/framework/Documentation

To parse and display the documentation on a live site, the markdow sources can be uploaded to the server.
Then the CLI cript is executed with the `make docu` option, which will send requests to GitHub's markdown parser.
The resulting HTML is then stored to the database.

It is also possible to perform these operations locally and then synchronize th remote database.
