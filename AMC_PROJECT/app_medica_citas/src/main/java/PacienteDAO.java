import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Ejemplo de conexión a la base de datos "app_medica_citas" mediante JDBC
 * y de las 4 operaciones CRUD (Crear, Leer, Actualizar, Eliminar) sobre
 * la tabla Pacientes. (Reto Final, punto 13)
 *
 * Requiere el driver de MySQL/MariaDB (mysql-connector-j) en el classpath.
 */
public class PacienteDAO {

    // Ajusta host, puerto, usuario y contraseña a tu entorno local
    private static final String URL = "jdbc:mysql://localhost:3306/app_medica_citas?useSSL=false&serverTimezone=UTC";
    private static final String USUARIO = "root";
    private static final String PASSWORD = "";

    /** Abre y regresa una conexión JDBC a la base de datos. */
    private Connection conectar() throws SQLException {
        try {
            // Registro explícito del driver (evita "No suitable driver found"
            // si el classpath no carga automáticamente el META-INF/services del jar)
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new SQLException("No se encontró el driver de MySQL (mysql-connector-j) en el classpath. "
                    + "Verifica que el .jar esté agregado en Libraries del proyecto.", e);
        }
        return DriverManager.getConnection(URL, USUARIO, PASSWORD);
    }

    // =====================================================
    // CREATE — Alta de un paciente nuevo
    // Usa el procedimiento almacenado sp_registrar_paciente,
    // que internamente crea el registro en Usuarios y en Pacientes
    // dentro de una sola transacción.
    // =====================================================
    public int crearPaciente(String nombre, String apPaterno, String apMaterno,
                              java.sql.Date fechaNacimiento, char genero, String curp,
                              String telefono, String correo, String contrasenaHash) {
        String sql = "{CALL sp_registrar_paciente(?,?,?,?,?,?,?,?,?,?)}";
        try (Connection con = conectar();
             CallableStatement cs = con.prepareCall(sql)) {

            cs.setString(1, nombre);
            cs.setString(2, apPaterno);
            cs.setString(3, apMaterno);
            cs.setDate(4, fechaNacimiento);
            cs.setString(5, String.valueOf(genero));
            cs.setString(6, curp);
            cs.setString(7, telefono);
            cs.setString(8, correo);
            cs.setString(9, contrasenaHash);
            cs.registerOutParameter(10, Types.INTEGER); // p_id_paciente

            cs.execute();
            int idGenerado = cs.getInt(10);
            System.out.println("Paciente creado con id_paciente = " + idGenerado);
            return idGenerado;

        } catch (SQLException e) {
            System.err.println("Error al crear paciente: " + e.getMessage());
            return -1;
        }
    }

    // =====================================================
    // READ — Lectura de todos los pacientes (o de uno por id)
    // =====================================================
    public List<String> leerPacientes() {
        String sql = "SELECT id_paciente, nombre, ap_paterno, correo, telefono FROM Pacientes ORDER BY id_paciente";
        List<String> resultado = new ArrayList<>();

        try (Connection con = conectar();
             PreparedStatement ps = con.prepareStatement(sql);
             ResultSet rs = ps.executeQuery()) {

            while (rs.next()) {
                String fila = rs.getInt("id_paciente") + " - "
                        + rs.getString("nombre") + " " + rs.getString("ap_paterno")
                        + " (" + rs.getString("correo") + ", " + rs.getString("telefono") + ")";
                resultado.add(fila);
            }

        } catch (SQLException e) {
            System.err.println("Error al leer pacientes: " + e.getMessage());
        }
        return resultado;
    }

    // =====================================================
    // UPDATE — Actualiza el teléfono y correo de un paciente existente
    // =====================================================
    public boolean actualizarPaciente(int idPaciente, String nuevoTelefono, String nuevoCorreo) {
        String sql = "UPDATE Pacientes SET telefono = ?, correo = ? WHERE id_paciente = ?";

        try (Connection con = conectar();
             PreparedStatement ps = con.prepareStatement(sql)) {

            ps.setString(1, nuevoTelefono);
            ps.setString(2, nuevoCorreo);
            ps.setInt(3, idPaciente);

            int filasAfectadas = ps.executeUpdate();
            System.out.println("Filas actualizadas: " + filasAfectadas);
            return filasAfectadas > 0;

        } catch (SQLIntegrityConstraintViolationException dup) {
            // Se dispara si el nuevo correo ya existe en otro paciente (UNIQUE)
            System.err.println("Error: el correo ya está en uso por otro paciente.");
            return false;
        } catch (SQLException e) {
            System.err.println("Error al actualizar paciente: " + e.getMessage());
            return false;
        }
    }

    // =====================================================
    // DELETE — Elimina un paciente por id
    // (dispara en cascada el borrado de sus Citas/Historial/Pagos, según
    // las llaves foráneas ON DELETE CASCADE definidas en la base)
    // =====================================================
    // ========================================================
// DELETE (Borrado Lógico) — Cambia el estatus a 'Inactivo'
// ========================================================
public boolean eliminarPaciente(int idPaciente) {
    // Cambiamos el DELETE por un UPDATE para conservar el registro histórico
    String sql = "UPDATE Pacientes SET estatus = 'Inactivo' WHERE id_paciente = ?";
    
    try (Connection con = conectar();
         PreparedStatement ps = con.prepareStatement(sql)) {
        
        ps.setInt(1, idPaciente);
        int filasAfectadas = ps.executeUpdate();
        
        if (filasAfectadas > 0) {
            System.out.println("Borrado lógico exitoso: El paciente pasó a estatus 'Inactivo'.");
        } else {
            System.out.println("No se encontró el paciente con el ID especificado.");
        }
        
        return filasAfectadas > 0;
        
    } catch (SQLException e) {
        System.err.println("Error al realizar el borrado lógico del paciente: " + e.getMessage());
        return false;
    }
}

    // =====================================================
    // Demostración de uso de las 4 operaciones
    // =====================================================
    public static void main(String[] args) {
        PacienteDAO dao = new PacienteDAO();

        // CREATE
        int nuevoId = dao.crearPaciente("Ana", "Torres", "Gil",
                java.sql.Date.valueOf("1995-04-10"), 'F', "TOGA950410MMSXXX01",
                "7779990000", "ana.torres@correo.com", "HASH$demo$x1");

        // READ
        System.out.println("\n--- Listado de pacientes ---");
        for (String fila : dao.leerPacientes()) {
            System.out.println(fila);
        }

        // UPDATE
        if (nuevoId > 0) {
            dao.actualizarPaciente(nuevoId, "7771112222", "ana.torres.nueva@correo.com");
        }

        // DELETE
        if (nuevoId > 0) {
            dao.eliminarPaciente(nuevoId);
        }
    }
}