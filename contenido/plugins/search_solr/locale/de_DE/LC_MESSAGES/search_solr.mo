��          �   %   �      P     Q     `     l     y     �     �  
   �  
   �     �     �     �     �                    *     ;     L     [     i          �     �     �     �  a  �     $     4  )   A  1   k  B   �  -   �          &  ,   7     d     |     �  u   �  J     P   a  Y   �  b     ,  o  _   �	  �   �	     �
  5   	     ?     F  M   K                                                     	                                                       
                 CLIENT_OPTIONS DESCRIPTION DESCR_DELETE DESCR_HOSTNAME DESCR_LOGIN DESCR_PASSWORD DESCR_PATH DESCR_PORT DESCR_PROXY_HOST DESCR_PROXY_LOGIN DESCR_PROXY_PASSWORD DESCR_PROXY_PORT DESCR_REINDEX DESCR_RELOAD DESCR_SECURE DESCR_SSL_CAINFO DESCR_SSL_CAPATH DESCR_SSL_CERT DESCR_SSL_KEY DESCR_SSL_KEYPASSWORD DESCR_TIMEOUT DESCR_WT OPTION VALUE WARNING_INVALID_CLIENT_OPTIONS Project-Id-Version: CONTENIDO Solr
Report-Msgid-Bugs-To: 
PO-Revision-Date: 2023-03-05 17:41+0100
Last-Translator: Murat Purç <murat@purc.de>
Language-Team: Marcus Gnaß <marcus.gnass@4fb.de>
Language: de_DE
MIME-Version: 1.0
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit
X-Poedit-KeywordsList: i18n
X-Generator: Poedit 3.2.2
 Client Optionen Beschreibung Artikel des aktuellen Mandanten löschen. Der Name des Solr-Servers. <b>Ohne Protokoll!</b> Der Benutzername für die HTTP-Authentifizierung, falls vorhanden. Das Passwort für die HTTP-Authentifizierung. Der Pfad zum Solr-Core. Die Port-Nummer. Der Name des Proxy-Servers, falls vorhanden. Der Proxy-Benutzername. Das Proxy-Passwort. Der Proxy-Port. Artikel des aktuellen Mandanten reindizieren. Artikel die offline oder nicht suchbar sind werden dabei übersprungen. Index nachladen entsprechend seiner Konfiguration in der Datei schema.xml. Boolscher Wert der angibt ob im sicheren Modus verbunden werden soll oder nicht. Name der Datei, das ein oder mehrere CA Zertifikate enthält um den Peer zu verifizieren. Name des Verzeichnisses, das ein oder mehrere CA Zertifikate enthält um den Peer zu verifizieren. Dateiname zu einer PEM-formatierten Datei die den privaten Schlüssel und das private Zertifikat (in dieser Reihenfolge aneinandergehängt) enthält. Bitte beachten Sie, dass wenn die ssl_cert-Datei ausschließlich das private Zertifikat enthält, eine gesonderte ssl_key-Datei angegeben werden muss. Dateiname zu einer PEM-formatierten Datei die ausschließlich den privaten Schlüssel enthält. Password für den privaten Schlüssel. Die ssl_keypassword-Option ist eine Pflichtangabe wenn die ssl_cert- oder ssl_key-Option gesetzt ist. Dies ist die maximale Zeit in Sekunden die für die HTTP Datenübertragungsoperation erlaubt ist. Standardwert ist 30 Sekunden. Der Name des 'response writers', z.B. xml, phpnative. Option Wert Artikel konnte nicht indizier werden, da Solr nicht korrekt konfiguriert ist. 