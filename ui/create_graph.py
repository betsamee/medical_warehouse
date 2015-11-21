#!/usr/bin/python

"""
* Python script allowing to generate (using NetworkX) a connected graph from a list of coocurrences
* Receives as parameter batchId to analyze
 """
    
import _mysql
import MySQLdb as mdb
import sys
import os
import networkx as nx
import matplotlib as mp

mp.use('Agg')

import matplotlib.pyplot as plt


execfile('config.py')
execfile('dbclass.py')


G=nx.Graph()

if __name__ == '__main__':
    try:
        db = MySQL_db_handler(HOST,PORT,USER,PASSWORD)
        db.connectDB(DB)
        
        """ select 25 top coocurrences to build a graph """
        result = db.queryDB("SELECT SUBSTRING(UMC_Translation,1,14), SUBSTRING(A.UMC_Translation2,1,14),ANR_Coocurrences  FROM Analysis_Results INNER JOIN UMLS_Correspondance on ANR_TextHeadingId1 = UMC_Code INNER JOIN  (SELECT UMC_Code as UMC_Code2,UMC_Translation as UMC_Translation2  FROM UMLS_Correspondance)A ON A.UMC_Code2 = ANR_TextHeadingId2  WHERE ANR_DataSetId = "+sys.argv[1]+" ORDER BY ANR_Coocurrences DESC LIMIT 25")
         
        """ 
        Graph contruction adapted from the code example of Aric Hagberg (hagberg@lanl.gov) in Tutorial for Networkx
        https://networkx.github.io/documentation/latest/examples/drawing/labels_and_colors.html?highlight=position
        (open source code)
        """
        G=nx.Graph()
       
        """ allows to draw stronger edges for strong coocurrences """
        for entry in result:
            G.add_edge(entry[0],entry[1],weight=entry[2]/3000)
       
        elarge=[(u,v) for (u,v,d) in G.edges(data=True) if d['weight'] >0.5]
        esmall=[(u,v) for (u,v,d) in G.edges(data=True) if d['weight'] <=0.5]
        
        pos=nx.circular_layout(G,scale=5,dim=2)

        nx.draw_networkx_nodes(G,pos,node_size=4000,linewidths=1)
        nx.draw_networkx_edges(G,pos,edgelist=elarge,
                            width=4)
        nx.draw_networkx_edges(G,pos,edgelist=esmall,
                            width=1,alpha=0.5,edge_color='b')

        nx.draw_networkx_labels(G,pos,font_size=8,font_family='sans-serif')

        plt.axis('off')
        plt.savefig("tmp/"+sys.argv[1]+".png")
       
        """ Cleans the batchId from temporary Result table """ 
        """ result = db.queryDB("DELETE FROM Analysis_Results WHERE ANR_DataSetId = "+sys.argv[1]) """
        
    except _mysql.Error, e:
      
        print "Error %d: %s" % (e.args[0], e.args[1])
        sys.exit(1)
    
    finally:
        
        if db._con:
            db.closeDB()
