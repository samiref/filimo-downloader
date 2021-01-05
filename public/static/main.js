var xhr = new XMLHttpRequest();
xhr.onreadystatechange = function(){
    if (xhr.readyState === 4){
        var content = xhr.responseText;
        if(content.length > 0)
            document.getElementById('items').innerHTML = content;

        console.log(xhr.readyState);
        loading(false);
    }

};

function loading(status)
{
    if(status == true)
    {
        document.getElementById('loading').innerHTML = '<img src="/static/loading.gif" />';
    }
    else
    {
        setTimeout(function(){
            document.getElementById('loading').innerHTML = "";
        }, 500);
    }
}

function process()
{
    loading(true);
    var add_new = document.getElementById('auto_download').checked;
    if(add_new == true)
        add_new = "add";
    else
        add_new = "";
    xhr.open('GET', '/process-queue?echo&' + add_new);
    xhr.send();
}