""" DB Handler Class """
class db_handler:
    def __init__(self,host,port,user,password):
        self._host = host
        self._port = port
        self._user = user
        self._password = password

""" MySQL DB Handler Class """
class MySQL_db_handler(db_handler):
    def connectDB(self,dbname):
        self._con = _mysql.connect(self._host, self._user, self._password, dbname)

    def queryDB(self,sql):
        self._con.query(sql)
        return self._con.use_result()

    def closeDB(self):
        self._con.close()