import mysql.connector
from datetime import datetime, timedelta
import random
from tqdm import tqdm

# Configuración de conexión a la base de datos
def conectar_bd():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="1123",
        database="bitacora1"
    )

# Función para generar datos
def generar_datos_lote(fecha_inicio, fecha_fin):
    datos = []
    fecha_actual = fecha_inicio
    while fecha_actual <= fecha_fin:
        valor = round(random.uniform(10.0, 200.0), 1)
        estado = random.choice(["OK", "ALERT"])
        datos.append((fecha_actual, valor, estado))
        fecha_actual += timedelta(minutes=1)
    return datos

# Función para insertar datos en lotes
def insertar_datos(conn, fecha_inicio, fecha_fin, nombre_tabla, id_equipos, tamano_lote=1000):
    cursor = conn.cursor()
    datos = generar_datos_lote(fecha_inicio, fecha_fin)
    total_registros = len(datos)

    # Consulta de inserción modificada para incluir id_equipos
    insert_query = f"INSERT INTO {nombre_tabla} (id_equipos, fecha_hora, valor, estado) VALUES (%s, %s, %s, %s)"
    
    with tqdm(total=total_registros, desc=f"Inserción en {nombre_tabla}", ncols=100) as pbar:
        for i in range(0, total_registros, tamano_lote):
            lote = [(id_equipos, *registro) for registro in datos[i:i+tamano_lote]]
            try:
                cursor.executemany(insert_query, lote)
                conn.commit()
                pbar.update(len(lote))
            except mysql.connector.Error as e:
                print(f"Error al insertar en {nombre_tabla}: {e}")
                conn.rollback()

# Función para solicitar el ID de equipos desde la base de datos
def solicitar_id_equipos():
    try:
        conn = conectar_bd()
        cursor = conn.cursor()
        cursor.execute("SELECT id_equipo FROM equipos")  # Usamos 'id_equipo' de la tabla 'equipos'
        equipos = cursor.fetchall()
        
        if not equipos:
            print("No hay equipos disponibles en la base de datos.")
            return None
        
        print("Seleccione un id_equipos disponible:")
        for idx, equipo in enumerate(equipos, 1):
            print(f"{idx}. ID: {equipo[0]}")

        while True:
            try:
                seleccion = int(input(f"Seleccione el número del id_equipos (1-{len(equipos)}): "))
                if 1 <= seleccion <= len(equipos):
                    return equipos[seleccion - 1][0]
                else:
                    print("Selección fuera de rango. Intente de nuevo.")
            except ValueError:
                print("Error: Ingrese un número válido.")
    except mysql.connector.Error as e:
        print(f"Error de conexión a la base de datos: {e}")
    finally:
        if conn:
            conn.close()

# Solicitar fechas y horas con validación
def solicitar_fechas():
    while True:
        try:
            fecha_inicio = input("Ingrese la fecha y hora de inicio (YYYY-MM-DD HH:MM:SS): ")
            fecha_fin = input("Ingrese la fecha y hora final (YYYY-MM-DD HH:MM:SS): ")
            fecha_inicio = datetime.strptime(fecha_inicio, "%Y-%m-%d %H:%M:%S")
            fecha_fin = datetime.strptime(fecha_fin, "%Y-%m-%d %H:%M:%S")
            if fecha_inicio > fecha_fin:
                raise ValueError("La fecha y hora de inicio no puede ser mayor que la fecha y hora final.")
            return fecha_inicio, fecha_fin
        except ValueError as e:
            print(f"Error: {e}. Intente de nuevo.")

# Main
def main():
    id_equipos = solicitar_id_equipos()  # Solicitar el id_equipos primero
    if not id_equipos:
        print("No se pudo seleccionar un id_equipos. Terminando ejecución.")
        return

    fecha_inicio, fecha_fin = solicitar_fechas()  # Solicitar las fechas después

    tablas = ["variable1", "variable2", "variable3", "variable4", "variable5", "variable6", "variable7", "variable8"]

    try:
        with conectar_bd() as conn:
            for tabla in tablas:
                print(f"Insertando datos en {tabla}...")
                insertar_datos(conn, fecha_inicio, fecha_fin, tabla, id_equipos)
        print("Datos insertados en todas las tablas.")
    except mysql.connector.Error as e:
        print(f"Error de conexión: {e}")

if __name__ == "__main__":
    main()
