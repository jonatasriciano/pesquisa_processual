function getCategorias() {
    $.ajax({
        url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/lista-categoria',
        type: 'get',
        dataType: 'json',
        success: function(data) {
            if (data.length) {
                var categorias = '<tr><th>Descrição</th><th>Tabelas Domínios</th><th colspan="2">Opções</th></tr>';
                for (var i = 0; i < data.length; i++) {
                    categorias = categorias + '<tr>';
                    categorias = categorias + '<td id="categoria_d_' + data[i]['codigo'] + '">' + data[i]['descricao'] + '</td>';
                    categorias = categorias + '<td id="categoria_t_' + data[i]['codigo'] + '">' + data[i]['tabela'] + '</td>';
                    categorias = categorias + '<td>';
                    categorias = categorias + '    <a href="javascript:void(0);" onclick="javascript:removeCategoria(' + data[i]['codigo'] + ')">';
                    categorias = categorias + '        <span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>';
                    categorias = categorias + '    </a>';
                    categorias = categorias + '</td>';
                    categorias = categorias + '<td id="categoria_i_' + data[i]['codigo'] + '">';
                    categorias = categorias + '    <a href="javascript:void(0);" onclick="javascript:editaCategoria(' + data[i]['codigo'] + ')">';
                    categorias = categorias + '        <span class="glyphicon glyphicon-pencil text-primary" aria-hidden="true"></span>';
                    categorias = categorias + '    </a>';
                    categorias = categorias + '</td>';
                    categorias = categorias + '</tr>';
                }
                $('.lista-categorias').html(categorias);
            }
        },
        error: function(resp) {
            window.alert('Houve um erro na tentativa de obter as categorias.');
        }
    });
}
getCategorias();

function getContasBancarias() {
    $.ajax({
        url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/lista-contas-bancarias',
        type: 'get',
        dataType: 'json',
        success: function(data) {
            if (data.length) {
                var contas_bancarias = '<tr><th>Descrição</th><th>Conta Bancária</th><th>Tabelas Domínios</th><th colspan="2">Opções</th></tr>';
                for (var i = 0; i < data.length; i++) {
                    contas_bancarias = contas_bancarias + '<tr>';
                    contas_bancarias = contas_bancarias + '<td id="conta_bancaria_d_' + data[i]['codigo'] + '">' + data[i]['descricao'] + '</td>';
                    contas_bancarias = contas_bancarias + '<td id="conta_bancaria_c_' + data[i]['codigo'] + '">' + data[i]['conta_bancaria'] + '</td>';
                    contas_bancarias = contas_bancarias + '<td id="conta_bancaria_t_' + data[i]['codigo'] + '">' + data[i]['tabela'] + '</td>';
                    contas_bancarias = contas_bancarias + '<td>';
                    contas_bancarias = contas_bancarias + '    <a href="javascript:void(0);" onclick="javascript:removeContaBancaria(' + data[i]['codigo'] + ')">';
                    contas_bancarias = contas_bancarias + '        <span class="glyphicon glyphicon-remove text-danger" aria-hidden="true"></span>';
                    contas_bancarias = contas_bancarias + '    </a>';
                    contas_bancarias = contas_bancarias + '</td>';
                    contas_bancarias = contas_bancarias + '<td id="conta_bancaria_i_' + data[i]['codigo'] + '">';
                    contas_bancarias = contas_bancarias + '    <a href="javascript:void(0);" onclick="javascript:editaContaBancaria(' + data[i]['codigo'] + ')">';
                    contas_bancarias = contas_bancarias + '        <span class="glyphicon glyphicon-pencil text-primary" aria-hidden="true"></span>';
                    contas_bancarias = contas_bancarias + '    </a>';
                    contas_bancarias = contas_bancarias + '</td>';
                    contas_bancarias = contas_bancarias + '</tr>';
                }
                $('.lista-contas-bncarias').html(contas_bancarias);
            }
        },
        error: function(resp) {
            window.alert('Houve um erro na tentativa de obter as contas bancárias.');
        }
    });
}
getContasBancarias();

function editaCategoria(id) {
    var descricao   = $('#categoria_d_' + id).html();
    var tabela      = $('#categoria_t_' + id).html();
    $('#categoria_d_' + id).html('<input class="form-control input-sm" type="text" value="' + descricao + '" />');
    $('#categoria_t_' + id).html('<input class="form-control input-sm" type="text" value="' + tabela + '" />');
    $('#categoria_i_' + id).html('<a href="javascript:void(0);" onclick="javascript:salvaCategoria(' + id + ')"><span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span></a>');
}

function editaContaBancaria(id) {
    var descricao       = $('#conta_bancaria_d_' + id).html();
    var conta_bancaria  = $('#conta_bancaria_c_' + id).html();
    var tabela          = $('#conta_bancaria_t_' + id).html();
    $('#conta_bancaria_d_' + id).html('<input class="form-control input-sm" type="text" value="' + descricao + '" />');
    $('#conta_bancaria_c_' + id).html('<input class="form-control input-sm" type="text" value="' + conta_bancaria + '" />');
    $('#conta_bancaria_t_' + id).html('<input class="form-control input-sm" type="text" value="' + tabela + '" />');
    $('#conta_bancaria_i_' + id).html('<a href="javascript:void(0);" onclick="javascript:salvaContaBancaria(' + id + ')"><span class="glyphicon glyphicon-ok text-success" aria-hidden="true"></span></a>');
}

function removeCategoria(id) {

    if (id != '') {

        $.ajax({
            url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/remove-categoria',
            type: 'post',
            data: {
                'codigo' : id
            },
            dataType: 'json',
            success: function(data) {
                if (data) {
                    if (data.status) {                    
                        window.alert(data.mensagem);
                        getCategorias();
                    } else {
                        window.alert(data.mensagem);
                    }
                } else {
                    window.alert('Não houve retorno do método removeCategoria');
                }
            },
            error: function(resp) {
                window.alert('Houve um erro na tentativa de remover a categoria.');
            }
        });
    } else {
        window.alert('Código para remoção da categoria não encontrado!');
    }
}

function removeContaBancaria(id) {

    if (id != '') {

        $.ajax({
            url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/remove-conta-bancaria',
            type: 'post',
            data: {
                'codigo' : id
            },
            dataType: 'json',
            success: function(data) {
                if (data) {
                    if (data.status) {
                        window.alert(data.mensagem);
                        getContasBancarias();
                    } else {
                        window.alert(data.mensagem);
                    }
                } else {
                    window.alert('Não houve retorno do método removeContaBancaria');
                }
            },
            error: function(resp) {
                window.alert('Houve um erro na tentativa de remover a conta bancária.');
            }
        });
    } else {
        window.alert('Código para remoção da conta bancária não encontrado!');
    }
}

function salvaCategoria(id) {
    
    if (id != '') {
        var descricao   = $('#categoria_d_' + id + ' input').val();
        var tabela      = $('#categoria_t_' + id + ' input').val();
    } else {
        var descricao   = $('#categoria_nova_d').val();
        var tabela      = $('#categoria_nova_t').val();
    }

    if (descricao.length && tabela.length) {

        $.ajax({
            url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/salva-categoria',
            type: 'post',
            data: {
                'codigo'    : id,
                'descricao' : descricao,
                'tabela'    : tabela
            },
            dataType: 'json',
            success: function(data) {
                if (data) {
                    if (data.status) {
                        window.alert(data.mensagem);
                        if (id == '') {
                            $('#categoria_nova_d').val('');
                            $('#categoria_nova_t').val('');
                        }
                        getCategorias();
                    } else {
                        window.alert(data.mensagem);
                    }
                } else {
                    window.alert('Não houve retorno do método salvaCategoria');
                }
            },
            error: function(resp) {
                window.alert('Houve um erro na tentativa de salvar a(s) categoria(s).');
            }
        });
    } else {
        window.alert('Preencha os campos descricao e tabela da categoria pelo menos');
    }
}

function salvaContaBancaria(id) {

    if (id != '') {
        var descricao       = $('#conta_bancaria_d_' + id + ' input').val();
        var conta_bancaria  = $('#conta_bancaria_c_' + id + ' input').val();
        var tabela          = $('#conta_bancaria_t_' + id + ' input').val();
    } else {
        var descricao       = $('#conta_bancaria_nova_d').val();
        var conta_bancaria  = $('#conta_bancaria_nova_c').val();
        var tabela          = $('#conta_bancaria_nova_t').val();
    }

    if (descricao.length && tabela.length) {

        $.ajax({
            url: getUrlCompleta() + '/index.php?codigo=' + getCodigo() + '&usuario=' + getUsuario() + '&r=cp/financeiro/salva-conta-bancaria',
            type: 'post',
            data: {
                'codigo'            : id,
                'descricao'         : descricao,
                'conta_bancaria'    : conta_bancaria,
                'tabela'            : tabela
            },
            dataType: 'json',
            success: function(data) {
                if (data) {
                    if (data.status) {
                        window.alert(data.mensagem);
                        if (id == '') {
                            $('#conta_bancaria_nova_d').val('');
                            $('#conta_bancaria_nova_c').val('');
                            $('#conta_bancaria_nova_t').val('');
                        }
                        getContasBancarias();
                    } else {
                        window.alert(data.mensagem);
                    }
                } else {
                    window.alert('Não houve retorno do método salvaContaBancaria');
                }
            },
            error: function(resp) {
                window.alert('Houve um erro na tentativa de salvar a(s) conta(s) bancária(s).');
            }
        });
    } else {
        window.alert('Preencha os campos descricao e tabela da conta bancária pelo menos');
    }
}

function importaArquivo() {
    var validado = true;
    var nomeArquivo = $('#file').val();

    if (nomeArquivo == '') {
        alert('Selecione um arquivo CSV para efetuar a importação.');
        validado = false;
    } else {
        partesNome = nomeArquivo.split('.');
        if (partesNome[1].toLowerCase() != 'csv') {
            alert('É permitido fazer a seleção apenas de arquivos com a extensão "CSV".');
            validado = false;
        }
    }

    if (validado) {
        
        $('#frmImportacao').submit();
    }
}

//Reference: 
//https://www.onextrapixel.com/2012/12/10/how-to-create-a-custom-file-input-with-jquery-css3-and-php/
;(function($) {
  // Browser supports HTML5 multiple file?
  var multipleSupport = typeof $('<input/>')[0].multiple !== 'undefined',
      isIE = /msie/i.test( navigator.userAgent );

  $.fn.customFile = function() {

    return this.each(function() {

      var $file = $(this).addClass('custom-file-upload-hidden'), // the original file input
          $wrap = $('<div class="file-upload-wrapper">'),
          $input = $('<input type="text" class="file-upload-input" />'),
          // Button that will be used in non-IE browsers
          $button = $('<button type="button" class="file-upload-button">Selecione o arquivo CSV</button>'),
          // Hack for IE
          $label = $('<label class="file-upload-button" for="'+ $file[0].id +'">Selecione o arquivo CSV</label>');

      // Hide by shifting to the left so we
      // can still trigger events
      $file.css({
        position: 'absolute',
        left: '-9999px'
      });

      $wrap.insertAfter( $file )
        .append( $file, $input, ( isIE ? $label : $button ) );

      // Prevent focus
      $file.attr('tabIndex', -1);
      $button.attr('tabIndex', -1);

      $button.click(function () {
        $file.focus().click(); // Open dialog
      });

      $file.change(function() {

        var files = [], fileArr, filename;

        // If multiple is supported then extract
        // all filenames from the file array
        if ( multipleSupport ) {
          fileArr = $file[0].files;
          for ( var i = 0, len = fileArr.length; i < len; i++ ) {
            files.push( fileArr[i].name );
          }
          filename = files.join(', ');

        // If not supported then just take the value
        // and remove the path to just show the filename
        } else {
          filename = $file.val().split('\\').pop();
        }

        $input.val( filename ) // Set the value
          .attr('title', filename) // Show filename in title tootlip
          .focus(); // Regain focus

      });

      $input.on({
        blur: function() { $file.trigger('blur'); },
        keydown: function( e ) {
          if ( e.which === 13 ) { // Enter
            if ( !isIE ) { $file.trigger('click'); }
          } else if ( e.which === 8 || e.which === 46 ) { // Backspace & Del
            // On some browsers the value is read-only
            // with this trick we remove the old input and add
            // a clean clone with all the original events attached
            $file.replaceWith( $file = $file.clone( true ) );
            $file.trigger('change');
            $input.val('');
          } else if ( e.which === 9 ){ // TAB
            return;
          } else { // All other keys
            return false;
          }
        }
      });

    });

  };

  // Old browser fallback
  if ( !multipleSupport ) {
    $( document ).on('change', 'input.customfile', function() {

      var $this = $(this),
          // Create a unique ID so we
          // can attach the label to the input
          uniqId = 'customfile_'+ (new Date()).getTime(),
          $wrap = $this.parent(),

          // Filter empty input
          $inputs = $wrap.siblings().find('.file-upload-input')
            .filter(function(){ return !this.value }),

          $file = $('<input type="file" id="'+ uniqId +'" name="'+ $this.attr('name') +'"/>');

      // 1ms timeout so it runs after all other events
      // that modify the value have triggered
      setTimeout(function() {
        // Add a new input
        if ( $this.val() ) {
          // Check for empty fields to prevent
          // creating new inputs when changing files
          if ( !$inputs.length ) {
            $wrap.after( $file );
            $file.customFile();
          }
        // Remove and reorganize inputs
        } else {
          $inputs.parent().remove();
          // Move the input so it's always last on the list
          $wrap.appendTo( $wrap.parent() );
          $wrap.find('input').focus();
        }
      }, 1);

    });
  }
}(jQuery));
$('input[type=file]').customFile();