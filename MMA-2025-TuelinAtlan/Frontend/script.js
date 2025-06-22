// 1) GLOBALER STATE
let products = [];
let users    = [];
let cart     = [];
let currentUser      = null;
let editingProductId = null;

let stats = {
  stockByCategory: [],
  monthlySales:    []
};

// 2) DATEN LADEN via jQuery AJAX

function loadProducts(searchTerm = '') {
  const url = searchTerm
    ? `../backend/api/products.php?search=${encodeURIComponent(searchTerm)}`
    : '../backend/api/products.php';
  $.getJSON(url)
    .done(data => {
      products = data;
      showProducts();
      showAdminProducts();
    })
    .fail((_, status) => {
      console.error('loadProducts-Error:', status);
    });
}

function loadUsers() {
  $.getJSON('../backend/api/users.php?action=list')
    .done(data => {
      users = data;
      showUsers();
    })
    .fail((_, status) => {
      console.error('loadUsers-Error:', status);
    });
}

function loadStats() {
  $.getJSON('../backend/api/stats.php')
    .done(data => {
      stats = data;
      drawStockChart();
      drawSalesChart();
    })
    .fail((_, status) => {
      console.error('loadStats-Error:', status);
    });
}

// 3) ANZEIGE-FUNKTIONEN

function showProducts() {
  const grid = $('#productGrid').empty();
  products.forEach(p => {
    $('<div>')
      .addClass('product-card')
      .html(`
        <h3>${p.name}</h3>
        <p>${p.description}</p>
        <p><strong>${p.price} €</strong></p>
        <button class="btn-add" data-id="${p.id}">In den Warenkorb</button>
      `)
      .appendTo(grid);
  });
}

function showAdminProducts() {
  const list = $('#adminProductList').empty();
  products.forEach(p => {
    $('<div>').html(`
      ${p.name} – ${p.price} €
      <button class="btn-edit-prod" data-id="${p.id}">Bearbeiten</button>
      <button class="btn-del-prod" data-id="${p.id}">Löschen</button>
    `).appendTo(list);
  });
}

function showUsers() {
  const container = $('#userList').empty();
  users.forEach(u => {
    $('<div>').html(`
      ${u.first_name} ${u.last_name} (${u.email})
      <button class="btn-del-user" data-id="${u.id}">Löschen</button>
    `).appendTo(container);
  });
}




//Navigation

  // Funktion, die nur die gewünschte Section anzeigt
  function showSection(id) {
    document.querySelectorAll('.section')
      .forEach(sec => sec.classList.remove('active'));
    const sec = document.getElementById(id);
    console.log('Showing section:', sec);
    if (sec) sec.classList.add('active');
  }

  // Direkt nach DOM-Ready die Button-Handler binden
  document.addEventListener('DOMContentLoaded', () => {
    const navMap = {
      'nodeGoto-manga':  'manga',
      'nodeGoto-games':  'games',
      'nodeGoto-comics': 'comics',
      'nodeGoto-login':  'login',
      'nodeGoto-logout': 'home'   // Per Logout zurück zur 'home'-Section
    };

    Object.entries(navMap).forEach(([btnId, secId]) => {
      const btn = document.getElementById(btnId);
      if (btn) {
        btn.addEventListener('click', () => {
          if (secId === 'home' && btnId === 'nodeGoto-logout') {
            // Logout-Logik, dann Home
            logout();
          }
          showSection(secId);
        });
      }
    });
  });

  // Beispiel-Logout-Funktion
  function logout() {
    // ...deine Logout-Logik hier (z.B. Token löschen)
    console.log('User ausgeloggt');
  }






function updateUI() {
  $('#userInfo').text(currentUser ? currentUser.email : 'Nicht angemeldet');
  $('#authBtn').toggle(!currentUser);
  $('#logoutBtn').toggle(!!currentUser);
  $('#adminBtn').toggle(currentUser?.role === 'admin');
}

function renderCart() {
  const content = $('#cartContent').empty();
  const totalBox = $('#cartTotal');
  if (!cart.length) {
    content.html('<p>Ihr Warenkorb ist leer.</p>');
    totalBox.hide();
    return;
  }
  cart.forEach(i => {
    const p = products.find(pr => pr.id === i.productId);
    $('<div>').text(`${p.name} – Menge: ${i.quantity}`).appendTo(content);
  });
  const total = cart.reduce((s,i)=>s+i.quantity*i.price,0);
  $('#totalAmount').text(total.toFixed(2));
  totalBox.show();
}

// 6) JQUERY EVENT-BINDING & INITIALISIERUNG

$(function(){
  // Navigation
  $('#homeBtn').click(    () => showSection('home') );
  $('#productsBtn').click(()=> showSection('products') );
  $('#cartBtn').click(    ()=> showSection('cart') );
  $('#authBtn').click(    ()=> showSection('login') );
  $('#adminBtn').click(   ()=> showSection('admin') );
  $('#logoutBtn').click(() => {
    $.get('../backend/api/users.php?action=logout')
     .always(() => {
       currentUser = null;
       updateUI();
       showSection('home');
     });
  })

  // Form-Handler
  $('#loginForm').submit(async function(e){
    e.preventDefault();
    const email = $('#loginEmail').val(), pw = $('#loginPassword').val();
    const data = await $.ajax({
      url: '../backend/api/users.php?action=login',
      method: 'POST', contentType:'application/json',
      data: JSON.stringify({email,password:pw})
    });
    if (data.success) { currentUser = data.user; updateUI(); showSection('home'); }
    else alert('Login fehlgeschlagen');
  });

  $('#registerForm').submit(async function(e){
    e.preventDefault();
    const payload = {
      email: $('#regEmail').val(),
      firstName: $('#regFirstName').val(),
      lastName: $('#regLastName').val(),
      password: $('#regPassword').val()
    };
    const data = await $.ajax({
      url: '../backend/api/users.php?action=register',
      method: 'POST', contentType:'application/json',
      data: JSON.stringify(payload)
    });
    alert(data.success ? 'Registrierung erfolgreich' : 'Fehler bei Registration');
  });

  $('#productSearch').on('input', function(){
    loadProducts($(this).val());
  });

  $('#userSearch').on('input', function(){
    const q = $(this).val().toLowerCase();
    showUsers(users.filter(u =>
      u.email.toLowerCase().includes(q) ||
      u.first_name.toLowerCase().includes(q) ||
      u.last_name.toLowerCase().includes(q)
    ));
  });

  // Delegated Events für dynamische Buttons
  $(document).on('click', '.btn-add', function(){
    addToCart(+$(this).data('id'));
  });
  $(document).on('click', '.btn-del-prod', function(){
    deleteProduct(+$(this).data('id'));
  });
  $(document).on('click', '.btn-edit-prod', function(){
    startEditProduct(+$(this).data('id'));
  });
  $(document).on('click', '.btn-del-user', function(){
    deleteUser(+$(this).data('id'));
  });
  $('#productForm').submit(function(e){ e.preventDefault(); /* …Admin save…*/ });

  // Checkout-Button
  $('#cartTotal button').click(checkout);

  // APP STARTEVENT
  loadProducts();
  loadUsers();
  loadStats();
  updateUI();
});









