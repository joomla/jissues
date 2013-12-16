## Pagination

I searched the web for "PHP pagination MySQL" or similar, and this came up on the first page:
http://www.awcore.com/dev/1/3/Create-Awesome-PHPMYSQL-Pagination_en

I liked the way the author solved "the problem", the code looked acceptable... so I "forked" and Joomla!'d it :)
The CSS was somewhat conflicting (for me), so I took one of the beautiful styles from the author, and now it looks like this:

![Pagination](https://f.cloud.github.com/assets/33978/550842/960024f0-c31b-11e2-971c-c7d870320600.png)

For now it only generates "plain links" adding a `&page=n` parameter to the current URL.
When we implement the search functionality, this must be revised.
I can also imagine a JavaScript (AJAX) based solution using this.

**Note**: The license on this is very unclear. Seems that is provided as a "snippet". Maybe our legal department should have a look at this ;)
