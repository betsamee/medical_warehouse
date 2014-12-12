#!/usr/bin/python
print "Content-Type: text/html\n"
print "<html><header><title>Data Miner</title></header>"
print "<body>"

import _mysql
import sys
import os

execfile('config.py')
execfile('dbclass.py')

if __name__ == '__main__':
    try:
        db = MySQL_db_handler(HOST,PORT,USER,PASSWORD)
        db.connectDB(DB)
            
        result = db.queryDB("SELECT * FROM Clients")
        
        print result.fetch_row()[0][0]
        print "</body></html>"
    except _mysql.Error, e:
      
        print "Error %d: %s" % (e.args[0], e.args[1])
        sys.exit(1)
    
    finally:
        
        if db._con:
            db.closeDB()
