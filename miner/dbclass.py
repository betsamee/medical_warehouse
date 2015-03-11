""" 
 * Abstract class for DB handling abstraction
 *
 * @package default
 * @author samuel Levy  
"""
class db_handler:
    def __init__(self,host,port,user,password):
        self._host = host
        self._port = port
        self._user = user
        self._password = password

""" 
 * Extension of DBHandler for MySQL DB handling
 *
 * @package default
 * @author samuel Levy   
"""
class MySQL_db_handler(db_handler):
     
     """
     * Connects to the DB
     *
     * @return void
     * @author samuel levy  
     """
    def connectDB(self,dbname):
        self._con = _mysql.connect(self._host, self._user, self._password, dbname)

     """
     * Launches a query against the DB
     *
     * @return results of the query
     * @author samuel levy  
     """
    def queryDB(self,sql):
        self._con.query(sql)
        return self._con.use_result()
    
         """
     * Closes DB connection
     *
     * @return void
     * @author samuel levy  
     """
    def closeDB(self):
        self._con.close()