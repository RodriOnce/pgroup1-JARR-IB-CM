import random
import string

# Lista de palabras simples para el modo frase
palabras = ["gato", "sol", "nube", "teclado", "azul", "fuego", "luz", "montaña", "verde", "rápido"]

def generar_contraseña(longitud, mayus=0, minus=0, nums=0, symbols=0):
    if mayus + minus + nums + symbols > longitud:
        raise ValueError("La suma de tipos supera la longitud total.")

    resto = longitud - (mayus + minus + nums + symbols)

    password = (
        random.choices(string.ascii_uppercase, k=mayus) +
        random.choices(string.ascii_lowercase, k=minus + resto) +
        random.choices(string.digits, k=nums) +
        random.choices("!@#$%&*+-_?", k=symbols)
    )
    random.shuffle(password)
    return ''.join(password)

def generar_frase(n_palabras=4, separador='-'):
    return separador.join(random.choice(palabras) for _ in range(n_palabras))

# Ejemplo de uso:
modo = input("¿Modo contraseña o frase? (p/f): ").strip().lower()

if modo == 'p':
    l = int(input("Longitud total: "))
    m = int(input("Mayúsculas: "))
    mi = int(input("Minúsculas: "))
    n = int(input("Números: "))
    s = int(input("Símbolos: "))
    print("Contraseña generada:", generar_contraseña(l, m, mi, n, s))
else:
    n = int(input("¿Cuántas palabras en la frase?: "))
    print("Frase generada:", generar_frase(n))
