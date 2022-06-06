/*
 * Particiona o json de um webservice em arquivos
 */

/*var start = Date.now();

process.on("exit", function() {
  var end = Date.now();
  console.log("Time taken: %ds", (end - start)/1000);
});*/

var fs = require("fs"),
    request = require('request');
//valores enviados por parâmetro na chamada do método
var url = process.argv[2]; //url a ser executada
var dir = process.argv[3]; //diretório onde os arquivos serão salvos

request(url, function (error, response, body) {
    if (!error && response.statusCode == 200) {        
        var json  = JSON.parse(body);
        var total = json.resposta.length;
        var i     = 0;
        var next  = 0;
        var limit = 4000;
        for(j = 0;j < total;j++) {
            if(j%limit == 0 || j == 0) {
                var name = i+'.json';
                i++;
                if(j == 0) {
                    next = limit;
                }else{
                    next = j + limit; 
                }                
                var stream = fs.createWriteStream(dir + name);
                stream.write('[');
            }
            stream.write(JSON.stringify(json.resposta[j]));
            
            if(j == next-1 || j+1 == total){
                stream.write(']');
                stream.end();
            }else{                
                stream.write(',');
            }                        
        }
    }
})