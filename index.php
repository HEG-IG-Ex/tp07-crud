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
$confirmation = '';

// Variable du formulaire.
$som_id = '';
$edit_nom = '';
$edit_region = '';
$edit_altitude = '';
$confirmation = '';

/**
 * Class Sommet
 * 
 * Contient les opérations nécessaires à la recherche des sommets
 */
class Sommet
{

  // Propriétés.
  public $totalResults = 0;
  private $bdd;
  private $tri = 'som_nom';
  private $sens = '';
  private $limit = '';
  private $criteres = [];

  // Constructeur: Initialise la base de données.
  public function __construct()
  {
    try {
      $this->bdd = new mysqli(DB_HOST, DB_USER, DB_PSW, DB_NAME);
    } catch (Exception $e) {
      throw ($e);
    }
  }

  /**
   * Génère les conditions (where) de la requête selon les filtres qui
   * ont été sélectionnés.
   */
  public function filtrer($champ, $valeur, $operateur)
  {

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
  public function trier($tri, $sens)
  {
    switch ($tri) {
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
  public function paginer($page, $nombreParPage = NOMBRE_PAR_PAGE)
  {
    $this->limit = "limit " . ($page - 1) * $nombreParPage . ", $nombreParPage";
  }

  /**
   * Lance la requête et retourne les résultats.
   */
  public function rechercher($debug = false)
  {
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
      $res = [];
      while ($row = $rec->fetch_object()) {
        $res[] = $row;
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
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function inserer($nom, $region, $alt, $debug = false)
  {
    try {
      // Construction de la requête.
      $sql = "INSERT INTO sommets (som_nom, som_region, som_altitude) VALUES (?, ?, ?)";

      // Affiche la requête SQL en mode "debug".
      if ($debug) {
        echo "<p><em>$sql</em></p>";
      }

      // Exécution de la requête.
      $stmt = $this->bdd->prepare($sql);
      $stmt->bind_param("ssi", $nom, $region, $alt);
      $stmt->execute();

      $stmt->close();

      return "New records created successfully";
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function modifier($id, $nom, $region, $alt, $debug = false)
  {
    try {
      // Construction de la requête.
      $sql = "UPDATE sommets SET som_nom = ?, som_region = ?, som_altitude = ? WHERE som_id = ?";

      // Affiche la requête SQL en mode "debug".
      if ($debug) {
        echo "<p><em>$sql</em></p>";
      }

      // Exécution de la requête.
      $stmt = $this->bdd->prepare($sql);
      $stmt->bind_param("ssii", $nom, $region, $alt, $id);
      $stmt->execute();

      $stmt->close();

      return "Records modified successfully";
    } catch (Exception $e) {
      throw $e;
    }
  }

  public function supprimer($id, $debug = false)
  {
    try {
      // Construction de la requête.
      $sql = "DELETE FROM sommets WHERE som_id = ?";

      // Affiche la requête SQL en mode "debug".
      if ($debug) {
        echo "<p><em>$sql</em></p>";
      }

      // Exécution de la requête.
      $stmt = $this->bdd->prepare($sql);
      $stmt->bind_param("i", $id);
      $stmt->execute();

      $stmt->close();

      return "Records deleted successfully";
    } catch (Exception $e) {
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
    $sommet->filtrer('som_nom', $nom, 'like');
  }

  // Ajoute un filtre "region" si défini.
  if (isset($_GET['region']) && $_GET['region']) {
    $region = $_GET['region'];
    $sommet->filtrer('som_region', $region, 'like');
  }

  // Ajoute un filtre "altitude minimum" si défini.
  if (isset($_GET['alt_min']) && $_GET['alt_min']) {
    $alt_min = $_GET['alt_min'];
    $sommet->filtrer('som_altitude', $alt_min, '>');
  }

  // Ajoute un filtre "altitude maximum" si défini.
  if (isset($_GET['alt_max']) && $_GET['alt_max']) {
    $alt_max = $_GET['alt_max'];
    $sommet->filtrer('som_altitude', $alt_max, '<');
  }

  // Ajoute un filtre "tri" et "sens" si défini.
  if (isset($_GET['tri']) && $_GET['sens']) {
    $tri = $_GET['tri'];
    $sens = $_GET['sens'];
    $sommet->trier($tri, $sens);
  }

  // Ajoute la pagination.
  $page = (isset($_GET['page']) && $_GET['page']) ? $_GET['page'] : 1;
  $sommet->paginer($page);

  // Recherche les résultats sur le bouton "submit" ou un des boutons "page" a été cliqué.
  if (isset($_GET['submit']) || isset($_GET['page'])) {
    $resultat = $sommet->rechercher(false);
    $totalResults = $sommet->totalResults;
  }

  print_r($_POST);

  // Create 
  if (isset($_POST['edit_nom'])) {
    $edit_nom = $_POST['edit_nom'];
  }
  if (isset($_POST['edit_region'])) {
    $edit_region = $_POST['edit_region'];
  }
  if (isset($_POST['edit_altitude'])) {
    $edit_altitude = $_POST['edit_altitude'];
  }

  if (isset($_POST['save']) && !isset($_POST['som_id']) && !$_POST['som_id']) {
    $confirmation = $sommet->inserer($edit_nom, $edit_region, $edit_altitude, true);
    $sommet->rechercher(false);
  }

  // Modify
  if (isset($_POST['save']) && isset($_POST['som_id'])) {
    $som_id_edit = $_POST['som_id'];
    $confirmation = $sommet->modifier($som_id_edit, $edit_nom, $edit_region, $edit_altitude, true);
    $sommet->rechercher(false);
  }

  // Delete
  if (isset($_POST['delete']) && isset($_POST['som_id'])) {
    $som_id_delete = $_POST['som_id'];
    $confirmation = $sommet->supprimer($som_id_delete, true);
    $sommet->rechercher(false);
  }

  // Récupère l'erreur si elle existe.
} catch (Exception $e) {
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
              <option <?php if ($tri == "nom") {
                        echo 'selected';
                      } ?> value="nom">Nom</option>
              <option <?php if ($tri == "region") {
                        echo 'selected';
                      } ?> value="region">Région</option>
              <option <?php if ($tri == "altitude") {
                        echo 'selected';
                      } ?> value="altitude">Altitude</option>
            </select>
            <select class="form-select" name="sens">
              <option <?php if ($sens == "asc") {
                        echo 'selected';
                      } ?> value="asc">Ascendant</option>
              <option <?php if ($sens == "desc") {
                        echo 'selected';
                      } ?> value="desc">Descendant</option>
            </select>
          </div>
        </div>

      </div>

    </div>

    <br />

    <!-- Lance la recherche -->
    <button type="submit" name="submit" class="btn btn-primary">Rechercher</button>

    <hr />

    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary" id="creer">
      Créer
    </button>

    <!-- Pagination de la recherche -->
    <nav aria-label="Page navigation example">
      <ul class="pagination justify-content-center">
        <?php for ($pageNum = 0; $pageNum < $totalResults / NOMBRE_PAR_PAGE; $pageNum++) { ?>
          <li class="page-item <?php if ($page == $pageNum + 1) {
                                  echo 'active';
                                } ?>"><button type="submit" name="page" value="<?php echo $pageNum + 1 ?>" class="page-link"><?php echo $pageNum + 1 ?></a></li>
        <?php } ?>
      </ul>
    </nav>

    <!-- Affiche le résultat de la recherche -->
    <?php if ($resultat) { ?>
      <p><?php foreach ($resultat as $result) { ?>
      <div>
        <span data-name="<?php echo $result->som_nom ?>"><?php echo $result->som_nom ?></span>
        <span data-region="<?php echo $result->som_region ?>"><?php echo $result->som_region ?></span>
        <span data-altitude="<?php echo $result->som_altitude ?>"><?php echo $result->som_altitude ?></span>
        <button type="button" class="edit btn btn-secondary btn-sm" id="edit-<?php echo $result->som_id ?>">Modifier</button>
        <button type="button" class="delete btn btn-secondary btn-sm" id="delete-<?php echo $result->som_id ?>">Supprimer</button>
      </div>
    <?php } ?></p>
  <?php } ?></p>

  </form>

  <!-- Affichage de la modale pour le formulaire de création / modification -->
  <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <form method="post" action="">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editModalLabel">Remplissez les champs</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" class="form-control" id="som_id_edit" name="som_id" value="<?php echo $som_id ?>">
            <div class="mb-3">
              <label for="edit_nom" class="form-label">Nom</label>
              <input type="text" class="form-control" id="edit_nom" name="edit_nom" value="<?php echo $edit_nom ?>">
            </div>
            <div class="mb-3">
              <label for="edit_region" class="form-label">Région</label>
              <input type="text" class="form-control" id="edit_region" name="edit_region" value="<?php echo $edit_region ?>">
            </div>
            <div class="mb-3">
              <label for="edit_altitude" class="form-label">Altitude</label>
              <input type="text" class="form-control" id="edit_altitude" name="edit_altitude" value="<?php echo $edit_altitude ?>">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="save" id="save" class="btn btn-primary">Créer</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Affichage de la modale pour le formulaire de suppression -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <form method="post" action="">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteModalLabel">Suppression du sommet</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" class="form-control" id="som_id_delete" name="som_id" value="<?php echo $som_id ?>">
            <p>Êtes-vous sûr de vouloir supprimer le sommet ?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" name="delete" id="delete" class="btn btn-primary">Supprimer</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <!-- Affichage du Toast pour la confirmation de message -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="confirm-toast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo $confirmation ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Affichage du Toast pour les erreurs de message -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="error-toast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo $erreur ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Toast -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>

  <!-- Script personnalisé -->
  <script type="text/javascript">
    const editModal = new bootstrap.Modal(document.getElementById("editModal"), {});
    const deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"), {});

    // Clic sur un bouton Edit: Ouverture de la modale avec remplissage des champs du formulaire selon le sommet choisi.
    document.querySelectorAll("button.edit").forEach((i) => {
      i.addEventListener('click', (e) => {
        document.getElementById('som_id_edit').value = e.target.id.substring(5);
        [...e.target.parentNode.children].map(c => {
          if (c.getAttribute("data-name")) {
            document.getElementById('edit_nom').value = c.getAttribute("data-name");
          }
          if (c.getAttribute("data-region")) {
            document.getElementById('edit_region').value = c.getAttribute("data-region");
          }
          if (c.getAttribute("data-altitude")) {
            document.getElementById('edit_altitude').value = c.getAttribute("data-altitude");
          }
        });
        document.getElementById('save').innerHTML = 'Modifier';
        editModal.show();
      });
    });

    // Clic sur un bouton Delete: Ouverture de la modale avec remplissage du champ du formulaire ID du sommet choisi.
    document.querySelectorAll("button.delete").forEach((i) => {
      i.addEventListener('click', (e) => {
        document.getElementById('som_id_delete').value = e.target.id.substring(7);
        deleteModal.show();
      });
    });

    // Clic sur un bouton Créer: Ouverture de la modale avec réinitialisation des champs du formulaire.
    document.getElementById('creer').addEventListener('click', (e) => {
      document.getElementById('som_id_edit').value = '';
      document.getElementById('edit_nom').value = '';
      document.getElementById('edit_region').value = '';
      document.getElementById('edit_altitude').value = '';
      document.getElementById('save').innerHTML = 'Créer';
      editModal.show();
    });

    // Ouverture automatique du Toast confirm lorsque la variable PHP $confirmation n'est pas vide.
    <?php if ($confirmation) { ?>
      const confirmToast = new bootstrap.Toast(document.getElementById("confirm-toast"), {});
      confirmToast.show();
    <?php } ?>

    // Ouverture automatique du Toast error lorsque la variable PHP $erreur n'est pas vide.
    <?php if ($erreur) { ?>
      const errorToast = new bootstrap.Toast(document.getElementById("error-toast"), {});
      errorToast.show();
    <?php } ?>
  </script>
</body>

</html>