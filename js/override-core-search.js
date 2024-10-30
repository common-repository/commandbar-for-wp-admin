document.querySelector('button[type=submit].wp-block-search__button').addEventListener('click', function(e) {
    window.CommandBar.open();
    e.preventDefault();
});

document.querySelector('input[type=search].wp-block-search__input').addEventListener('click', function(e) {
    window.CommandBar.open();
    e.preventDefault();
});
