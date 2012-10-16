Luminous Changelog since 0.6.0
==============================

##v0.6.7-2 (10/6/12):
  - Fixed another problem with unnecessary scrollbars

##v0.6.7-1 (19/05/12):
  - Fixed a regression which introduced scrollbars on inline code

##v0.6.7 (24/04/12):

Likely to be the final release in 0.6 series. Further releases will be on the
0.7 tree.

- New stuff:
  - Ability to set custom line number for first line in output, 
    thanks [Martin Sikora](https://github.com/martinsik)
  - Line highlighting (click with JS) uses CSS transitions

- Fixes:
  - Small improvements to JavaDoc-like comment highlighting  
  - CSS scanner won't break on @media { ... } rules
  - CSS scanner won't break on @keyframe { ... } rules
  - CSS scanner will highlight round brackets in selectors (like :nth-child(n+1))
  - HTML output now a tiny bit compressed
  - HTML output with unconstrained height will scroll horizontally instead of
    spilling overflow.
  
  
##v0.6.6 (26/02/12):

Maintenance release

- Fixed: 
  - Parse errors on PHP 5.2.0 (due to using unescaped '$' in doubly quoted strings)

- Improved: 
  - Cache error behaviour is less ugly. Errors can more easily be detected
    programmatically, and suppressed (or handled silently). See the cache's docs
    on how to do this.
  - Made testing/developing on Windows slightly more possible. Don't expect miracles.
  

##v0.6.5 (15/10/11):

- New stuff:
  - kimono.css theme (based on the more famous Monokai theme)
  - versioncheck.php (in root) - Version checking script. Run from either a 
    browser or the command line to query the website's API as to the most 
    recent version and output whether you're running it.
  - style and client both have .htaccesses to ensure that they are in fact
    readable; this might be useful if for some reason you've put Luminous in
    a directory which a .htaccess forbids access to (e.g. somewhere in a 
    framework).

- Misc stuff:
  - Some really minor optimisations. Absolutely tiny. You won't notice them. 
  - Updated jQuery to 1.6.4
  - The external CSS output by `luminous::head_html() ` now have IDs set, 
    the theme is IDed as 'luminous-theme'. This makes changing the theme via
    JS a lot neater (see theme switcher example). Why didn't we think of this
    earlier?
  - SQL cache is a tiny bit faster as it does not try to purge old elements
    excessively anymore (max: once per 24 hours).
  - GitHub theme is a little bit cleaner with interpolated elements (including
    PHP short output tags)
  - Diff scanner: the scanner has been split into two. The old behaviour (where
    embedded source code was highlighted) has been renamed to diff-pretty, and 
    'diff' now represents a plain scanner which does not highlight embedded 
    code. If you want the old behaviour, use diff-pretty (valid code: 
    diffpretty. See the languages page for more aliases.). This is because 
    the pretty diff scanner is much slower and can encounter problems, so users
    may prefer a more reliable and faster but plain option.
  - Some of the JS examples have been fixed.

- Language fixes:
  - Support for Java annotations
  - Django scanner recognises {% comment %} ... {% endcomment %} blocks
  - Ruby scanner has been altered with respect to how it detects regular 
    expression literals. It is now similar to Kate/Kwrite's syntax 
    highlighting and should be slightly better at figuring out what's a 
    regex and what's a division operator. If it causes problems please 
    report it, preferably with an explanation of how Ruby's grammar works in 
    that particular case.
  - Ruby on Rails will now terminate comments at the end of the Rails block as
    well as newlines
  - Bash scanner will not go into heredoc mode inside ((...)) blocks; this 
    prevents false positives on shift operations
  - Bash scanner should get fewer false positives when picking out comments
  - Perl scanner recognises heredoc openings when the delimiter is preceded by
    a backslash
  - PHP scanner is a little more careful about detecting user-definitions of
    functions and classes, i.e. it correctly highlights class names after 
    implements and extends, and won't get confused by PHP5.3+ closures.
  - PHP Snippet mode will detect `<?php` correctly, should it be encountered.


##v0.6.4 (18/09/11):

- New stuff:
  - Django scanner
  - 'geoynx' theme now has a per-line highlight style

- Fixes: 
  - Fix Luminous not fully respecting rounded corners (border-radius) on the 
    outer-most div element.
  - A few typos and grammatical problems corrected in documentation

- Language fixes:
  - Added missing 'with' keyword for Python
  - Added some missing functions to Python (int, str, float, list, etc)
  - Fix theoretically possible bug where the HTML scanner cannot 'recover' 
    after it breaks from HTML-mode into server-side mode.
  - Fix occasional overzealous assert() triggering in HTML scanner
  - Fix occasional bug where ECMAScript's embedded XML literals would not break
    allow embedded server-side languages
  - Fix occasional bug where CSS and ECMAScript didn't always allow embedded 
    server-side languages

- Important stuff for developers:
  - The web language scanner's `server_tags` has been changed to a regular 
    expression. This is not backwardly compatible.

##v0.6.3-1 (06/08/11):

- Fixes:
  - Fix stupid bug where the cache will purge items after 9 days of inactivity,
    instead of 90 days. This is user-overridable by the 'cache-age' option, and
    will only affect installations where it was left to the default setting.

##v0.6.3 (22/06/11):

- New Stuff:
  - Ada language support
  - Cache can be stored in a MySQL table. See (the docs)[http://luminous.asgaard.co.uk/index.php/docs/show/cache/]

- Fixes
  - Disabled cache purging by default in the cache superclass, previously it
    was set to an hour and may have been invoked accidentally if you 
    insantiated a cache object yourself for some reason.
  - Check before invoking the command line arg parser that Luminous is really
    what's being executed, so that you can now include it from another 
    CLI program.

- Languages fixes:
  - Fix recognition of Perl's shell command strings (backticks) when the 
    string involves escape characters
  - Fix bug with Perl heredoc recognition not waiting until the next line to
    invoke heredoc parsing mode
  - Fix bug with Python not correctly recognising numeric formats with a 
    leading decimal point
  - Fix Ruby's %w and %W operators such that their contents are recognised as 
    strings split by whitespace, not one continual string
  - Highlight "${var}" string interpolation syntax in PHP scanner


##v0.6.2 (15/05/11):

- General: 
  - The User API's configuration settings has been changed internally, using
    the luminous::set() method will throw exceptions if you try to do something
    nonsensical.
  - Each configuration option is now documented fully in Doxygen 
    (LumiousOptions class).
  - High level user API's docs are bundled in HTML.
  - PHP 5.2 syntax error fixed in LaTeX formatter (did not affect 5.3+)

- New Stuff:
  - HTML full-page formatter, which produces a standalone HTML document. Use 
    'html-full' as the formatter.
  - Command line interface. Run ``php luminous.php --help`` to see the usage
  - Language guessing. Generally accurate for large sources of common 
    languages, with no guarantees in other situations. See 
    ``luminous::guess_language`` and ``luminous::guess_lanuage_full`` 
    in Doxygen for details. 

- Language fixes:
  - C/C++ had its #if 0... #endif nesting slightly wrong, it now works
    properly
  - Diff scanner should no longer get confused over formats (i.e. original, 
    context, or unified) if a line starts with a number.
  - PHP now recognises backtick delimited 'strings'
  - Ruby heredoc detection previously had a minor but annoying bug where 
    a heredoc declaration would kill all highlighting on the remainder of 
    that line. This now works correctly.
  - SQL recognises a much more complete set of words, though non-MySQL dialects
    are still under-represented

##v0.6.1 (29/04/11):

- General:
    - Certain versions of PCRE trigger *a lot* of bugs in the regular 
      expressions, which seemed to backtrack a lot even on very simple
      strings. Most (if not all) of these expressions have been rewritten
      to avoid this.
    - The above previously threw an exception: this is now true only if the
      debug flag is set, otherwise the failure is handled.
    - The User API should catch any exceptions Luminous throws in non-debug
      code. If one is caught, Luminous returns the input string wrapped in a 
      pre tag.
    - 'plain' is used as a default scanner in the User API (previously an
      exception was thrown if a scanner was unknown)
    - Fix bug where the User API's 'relative root' would collapse double slashes
      in protocols (i.e. http:// => http:/)
    - User API now throws Exception if the highlighting function is called with
      non-string arguments
    - Some .htaccesses are provided to prevent search engines/bots crawling the
      Luminous directories (many of the files aren't supposed to be executed
      individually and will therefore populate error logs should a bot
      discover a directory)
    - Minor tweaks to the geonyx theme
    - Obsolete JavaScript has been removed and replaced with a much less
      intrusive behaviour of double click the line numbers to hide them,
      js inclusion is disabled by default by User API.
    - Infinite loop bug in the abstract formatter/word wrap method fixed 
      (although this wasn't actually reachable by any of the formatters)

- Language fixes:
    - Pod/cut style comments in Perl should now work all the time
    - C/C++'s "#if 0 ... #endif" blocks (which are highlighted as comments) 
      now nest
    - Python recognises a list of exceptions as types

- New Stuff:
    - Go language support

-  Internal/Development:
    - Unit test of stateful scanner much more useful
    - Formatter base class unit test (tests/unit/formatter.php)
    - Syntax test for scanners (syntax.php)
    - Stateful scanner throws an exception if the initial state is popped
      (downgraded from an assertion)
    - Stateful scanner safety check no longer requires that an iteration
      advances the pointer as long as the state is changed
    - Coding standards applied in all formatters
    - All scanning classes have complete API documentation
    - Paste test (interface.php) works properly with Unicode

## v0.6.0 (16/04/11):
- 0.6.0 is a near-total rewrite with a lot of changes. The hosting has 
  moved from Google Code to GitHub and most code is freshly written.
- Changelog is restarted
