
== README ==

The readme for this plugin can be found in the 4fb Wiki (Confluence):

    https://projectdocs.4fb.de/pages/viewpage.action?pageId=11174364

== IMPROVEMENTS ==

- don't index articles in protected categories
- allow reindexing for single client
- allow to select custom Indexer or Searcher
- further admin actions:
    STATUS Get the status for a given core or all cores if no core is specified:
    CREATE Creates a new core based on preexisting instanceDir/solrconfig.xml/schema.xml, and registers it.
    RELOAD Done.
    RENAME Change the names used to access a core.
    SWAP Atomically swaps the names used to access two existing cores.
    UNLOAD Removes a core from Solr.
- index CMS_DATE as date
