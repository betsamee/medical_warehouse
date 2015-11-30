#!/usr/bin/python

"""
* Python script allowing to generate (using pytagcloud) a tag cloud
* Receives as parameter batchId to analyze
* https://github.com/atizo/PyTagCloud
 """
    
import _mysql
import MySQLdb as mdb
import sys
import os
import operator

import requests, collections, bs4
from pytagcloud import create_tag_image, make_tags, create_html_data ,LAYOUT_VERTICAL,LAYOUT_MIX,LAYOUT_MOST_HORIZONTAL,LAYOUT_HORIZONTAL
from pytagcloud.lang.counter import get_tag_counts
from string import Template



execfile('config.py')
execfile('dbclass.py')

text = ""

if __name__ == '__main__':
    try:
        db = MySQL_db_handler(HOST,PORT,USER,PASSWORD)
        db.connectDB(DB)
        
        """ select 30 top coocurrences to build a tag cloud
        performs normalization of the coocurrence (linear regression) in the sql directly for better performance  """
        
        sql = "SELECT UMC_Translation, A.UMC_Translation2,CEIL((ANR_Coocurrences - (SELECT MIN(ANR_Coocurrences)"   
        sql = sql + " FROM Analysis_Results "
        sql = sql + " INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code "
        sql = sql + " INNER JOIN  (SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2  FROM UMLS_Correspondance)A ON A.UMC_Code2 = ANR_TextHeadingId2"  
        sql = sql + " WHERE ANR_DataSetId = " + sys.argv[1]
        sql = sql + " ORDER BY ANR_Coocurrences DESC LIMIT 50)) * (10 - 1) / ((SELECT MAX(ANR_Coocurrences)"   
        sql = sql + " FROM Analysis_Results "
        sql = sql + " INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code "
        sql = sql + " INNER JOIN  (SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2  FROM UMLS_Correspondance)A ON A.UMC_Code2 = ANR_TextHeadingId2"  
        sql = sql + " WHERE ANR_DataSetId = " + sys.argv[1]
        sql = sql + " ORDER BY ANR_Coocurrences DESC LIMIT 50) - (SELECT MIN(ANR_Coocurrences)"   
        sql = sql + " FROM Analysis_Results "
        sql = sql + " INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code "
        sql = sql + " INNER JOIN  (SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2  FROM UMLS_Correspondance)A ON A.UMC_Code2 = ANR_TextHeadingId2"  
        sql = sql + " WHERE ANR_DataSetId = "  + sys.argv[1]
        sql = sql + " ORDER BY ANR_Coocurrences DESC LIMIT 50)) + 1) As NormalizedCoocurrence"
        sql = sql + " FROM Analysis_Results "
        sql = sql + " INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code "
        sql = sql + " INNER JOIN  (SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2  FROM UMLS_Correspondance)A ON A.UMC_Code2 = ANR_TextHeadingId2"  
        sql = sql + " WHERE ANR_DataSetId = " + sys.argv[1] 
        sql = sql + " ORDER BY ANR_Coocurrences DESC LIMIT 50"
        
        result = db.queryDB(sql)         
       
        """ build the text for the cloud """
        """text = text + " " + entry[0].replace(",","~").replace(" ","____")+"_"+entry[1].replace(",","").replace(" ","")"""
        for entry in result:
            for i in range(1,entry[2]):
                       text = text + " " + entry[0] + " " + entry[1]
        
        
        tags = make_tags(get_tag_counts(text),maxsize=120)
        create_tag_image(tags, 'tmp/cloud_'+ sys.argv[1] +'.png', size=(1024, 800), fontname='Lobster')
        
        """ Cleans the batchId from temporary Result table """ 
        result = db.queryDB("DELETE FROM Analysis_Results WHERE ANR_DataSetId = "+sys.argv[1])
        
    except _mysql.Error, e:
      
        print "Error %d: %s" % (e.args[0], e.args[1])
        sys.exit(1)
    
    finally:
        
        if db._con:
            db.closeDB()
