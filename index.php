<?php

  // Définit les constantes de connexions.
  define('DB_HOST', 'localhost');
  define('DB_NAME', 'sommets');
  define('DB_USER', 'root');
  define('DB_PSW', '');

  // Constante qui défini le nombre d'entrée maximum à afficher sur la pagination.
  define('NOMBRE_PAR_PAGE', 20);

  // Variables utilisées lors de la recherche.
  $nom = "";
  $region = "";
  $alt_min = "";
  $alt_max = "";
  $tri = "";
  $sens = "";
  $page = "";

  // Variables d'affichage.
  $totalResults = 0;
  $resultat = '';
  $erreur = '';

  /**
   * Class Sommet
   * 
   * Contient les opérations nécessaires à la recherche des sommets
   */
  class Sommet {

    // Propriétés.
    public $totalResults = 0;
    private $bdd;
    private $tri = 'som_nom';
    private $sens = '';
    private $limit = '';
    private $criteres = [];
  
    // Constructeur: Initialise la base de données.
    public function __construct() {
      try {
        $this->bdd = new mysqli(DB_HOST, DB_USER, DB_PSW, DB_NAME);
      } catch(Exception $e) {
        throw ($e);
      }
    }
  
    /**
     * Génère les conditions (where) de la requête selon les filtres qui
     * ont été sélectionnés.
     */
    public function filtre($champ, $valeur, $operateur) {

      // Si l'opérateur "like" a été définit, rajoute un % afin de sélectionner toutes
      // les entrées d'un champ commençant par la valeur qui a été définit.
      if (strtolower($operateur) == 'like') {
        $valeur .= '%';
      }

      // Si la valeur n'est pas numérique, ajoute des apostrophes (chaine de caractère)
      // et des barres obliques inversées pour protéger la requête contre les injections SQL
      // ou les bugs en cas de recherche avec un apostrophe.
      if (!is_numeric($valeur)) {
        $valeur = "'" . addslashes($valeur) . "'";
      } else {
        $valeur = addslashes($valeur);
      }

      // Ajoute les critère au tableaux des critères.
      $critere = "$champ $operateur $valeur";
      $this->criteres[] = $critere;
    }
  
    /**
     * Genère l'order (order by) de la requête selon les critères de tri
     * qui ont été définit.
     */
    public function tri($tri, $sens) {
      switch($tri) {
        case 'region':
          $this->tri = "som_region";
          break;
        case 'altitude':
          $this->tri = "som_altitude";
          break;
        default:
          $this->tri = "som_nom";
      }
      
      // Protège la requête contre les injections SQL
      // ou les bugs en cas de recherche avec un apostrophe.
      $this->sens = $sens ? addslashes($sens) : '';
    }
  
    /**
     * Définit la "limit" de la requête en fonction de la page sur laquelle on
     * se trouve.
     */
    public function pagination($page, $nombreParPage = NOMBRE_PAR_PAGE) {
      $this->limit = "limit " . ($page-1) * $nombreParPage . ", $nombreParPage";
    }
  
    /**
     * Lance la requête et retourne les résultats.
     */
    public function resultat($debug = false) {
      try {
        // Construction du filtre sur la bases du tableau de filtres.
        $where = count($this->criteres) ? "where " . implode(' AND ', $this->criteres) : '';

        // Construction de la requête.
        $sql = "SELECT * from sommets $where order by $this->tri $this->sens $this->limit";
  
        // Affiche la requête SQL en mode "debug".
        if ($debug) {
          echo "<p><em>$sql</em></p>";
        }
  
        // Exécution de la requête.
        $rec = $this->bdd->query($sql);
  
        // Parcours du résultat.
        $res = '';
        while ($row = $rec->fetch_object()) {
          $res .= "$row->som_nom $row->som_region $row->som_altitude<br>";
        }

        // Récupère le nombre total de résultat
        $sql = "SELECT count(*) as total from sommets $where order by $this->tri $this->sens";
        $rec = $this->bdd->query($sql);
        $this->totalResults = $rec->fetch_object()->total;

        // Si aucun résultat n'a été trouvé, retourne l'indication.
        if (!$res) {
          return 'Aucun résultat trouvé';
        }

        // Retourne les résultats sous forme de chaine de caractères.
        return $res;
      } catch(Exception $e) {
        throw $e;
      }
    }
  
  }
  
  // Execute le code.
  try {

    // Crée un nouveau sommet.
    $sommet = new Sommet();

    // Ajoute un filtre "nom" si défini.
    if (isset($_GET['nom']) && $_GET['nom']) {
      $nom = $_GET['nom'];
      $sommet->filtre('som_nom', $nom, 'like');
    }

    // Ajoute un filtre "region" si défini.
    if (isset($_GET['region']) && $_GET['region']) {
      $region = $_GET['region'];
      $sommet->filtre('som_region', $region, 'like');
    }

    // Ajoute un filtre "altitude minimum" si défini.
    if (isset($_GET['alt_min']) && $_GET['alt_min']) {
      $alt_min = $_GET['alt_min'];
      $sommet->filtre('som_altitude', $alt_min, '>');
    }

    // Ajoute un filtre "altitude maximum" si défini.
    if (isset($_GET['alt_max']) && $_GET['alt_max']) {
      $alt_max = $_GET['alt_max'];
      $sommet->filtre('som_altitude', $alt_max, '<');
    }

    // Ajoute un filtre "tri" et "sens" si défini.
    if (isset($_GET['tri']) && $_GET['sens']) {
      $tri = $_GET['tri'];
      $sens = $_GET['sens'];
      $sommet->tri($tri, $sens);
    }
    
    // Ajoute la pagination.
    $page = (isset($_GET['page']) && $_GET['page']) ? $_GET['page'] : 1;
    $sommet->pagination($page);

    // Recherche les résultats sur le bouton "submit" ou un des boutons "page" a été cliqué.
    if (isset($_GET['submit']) || isset($_GET['page'])) {
      $resultat = $sommet->resultat(true);
      $totalResults = $sommet->totalResults;
    }
  
  // Récupère l'erreur si elle existe.
  } catch(Exception $e) {
    $erreur = $e->getMessage();
  }
  

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <title>Sommets</title>
  </head>
  <body class="container">

    <h1>Recherche des sommets</h1>

    <form method="get" action="">

      <div class="row">

        <!-- Filtre pour la recherche (nom, region, alt_min et alt_max) sous forme de champs de texte -->
        <div class="col">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Filtres</h5>
              <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo $nom ?>">
              </div>
              <div class="mb-3">
                <label for="region" class="form-label">Région</label>
                <input type="text" class="form-control" id="region" name="region" value="<?php echo $region ?>">
              </div>
              <div class="mb-3">
                <label for="alt_min" class="form-label">Altitude minimum</label>
                <input type="text" class="form-control" id="alt_min" name="alt_min" value="<?php echo $alt_min ?>">
              </div>
              <div class="mb-3">
                <label for="alt_max" class="form-label">Altitude maximum</label>
                <input type="text" class="form-control" id="alt_max" name="alt_max" value="<?php echo $alt_max ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- Tri pour la recherche (tri et sens) sous forme de liste de sélection -->
        <div class="col">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Tri</h5>
              <select class="form-select" name="tri">
                <option <?php if ($tri == "nom") { echo 'selected'; } ?> value="nom">Nom</option>
                <option <?php if ($tri == "region") { echo 'selected'; } ?> value="region">Région</option>
                <option <?php if ($tri == "altitude") { echo 'selected'; } ?> value="altitude">Altitude</option>
              </select>
              <select class="form-select" name="sens">
                <option <?php if ($sens == "asc") { echo 'selected'; } ?> value="asc">Ascendant</option>
                <option <?php if ($sens == "desc") { echo 'selected'; } ?> value="desc">Descendant</option>
              </select>
            </div>
          </div>

        </div>

      </div>

      <br />

      <!-- Lance la recherche -->
      <button type="submit" name="submit" class="btn btn-primary">Rechercher</button>

      <hr />

      <!-- Pagination de la recherche -->
      <nav aria-label="Page navigation example">
        <ul class="pagination justify-content-center">
          <?php for ($pageNum = 0; $pageNum < $totalResults / NOMBRE_PAR_PAGE; $pageNum++) { ?>
            <li class="page-item <?php if ($page == $pageNum + 1) { echo 'active'; } ?>"><button type="submit" name="page" value="<?php echo $pageNum + 1 ?>" class="page-link"><?php echo $pageNum + 1 ?></a></li>
          <?php } ?>
        </ul>
      </nav>

    </form>

    <!-- Affiche le résultat de la recherche -->
    <p><?php echo $resultat ?></p>

    <!-- Affiche l'erreur -->
    <p class="text-bg-danger"><?php echo $erreur ?></p>

  </body>
</html>