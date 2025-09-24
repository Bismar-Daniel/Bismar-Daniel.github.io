import base64

with open("departament.jpg", "rb") as img_file:
    base64_str = base64.b64encode(img_file.read()).decode('utf-8')

data_uri = f"data:image/jpeg;base64,{base64_str}"

print(data_uri)
