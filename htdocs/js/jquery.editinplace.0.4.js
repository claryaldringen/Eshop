(function(){
    jQuery.fn.editable = function(){
        $(this).attr("contentEditable",true).addClass("editable")

        .focus(function(){
            $(this).data("editable",{originalContent:$(this).html()}) // Uložíme aktuální obsah políčka
            //.addClass("ui-state-active");
        })

        .blur(function(){
            var data = $(this).data("editable"); // data is reference
            if(data.originalContent != $(this).html())
                $(this).change(); // Call change event
            $(this).removeData("editable")
            //.removeClass("ui-state-active");
        });

        return this;
    };
})();