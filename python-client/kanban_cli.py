#!/usr/bin/env python3
import os
import json
import typer
import requests
from rich.console import Console
from rich.table import Table
from rich.panel import Panel
from dotenv import load_dotenv
from typing import Optional

load_dotenv()

app = typer.Typer(help="Kanban Board CLI Client")
console = Console()

API_URL = os.getenv("API_URL", "http://localhost:8000/api")
TOKEN_FILE = os.path.expanduser("~/.kanban_token")


def get_token() -> Optional[str]:
    if os.path.exists(TOKEN_FILE):
        with open(TOKEN_FILE, "r") as f:
            return f.read().strip()
    return None


def save_token(token: str):
    with open(TOKEN_FILE, "w") as f:
        f.write(token)


def clear_token():
    if os.path.exists(TOKEN_FILE):
        os.remove(TOKEN_FILE)


def get_headers():
    token = get_token()
    headers = {"Content-Type": "application/json", "Accept": "application/json"}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    return headers


def handle_response(response: requests.Response, success_message: str = None):
    if response.status_code >= 400:
        try:
            error = response.json().get("error", {})
            console.print(f"[red]Error: {error.get('message', 'Unknown error')}[/red]")
        except:
            console.print(f"[red]Error: {response.status_code}[/red]")
        raise typer.Exit(1)

    if success_message:
        console.print(f"[green]{success_message}[/green]")

    return response.json() if response.text else None


@app.command()
def login(
    email: str = typer.Option(..., prompt=True),
    password: str = typer.Option(..., prompt=True, hide_input=True),
):
    response = requests.post(
        f"{API_URL}/auth/login",
        json={"email": email, "password": password},
        headers={"Content-Type": "application/json", "Accept": "application/json"},
    )
    data = handle_response(response)
    save_token(data["token"])
    console.print(f"[green]Logged in as {data['user']['name']}[/green]")


@app.command()
def register(
    name: str = typer.Option(..., prompt=True),
    email: str = typer.Option(..., prompt=True),
    password: str = typer.Option(..., prompt=True, hide_input=True),
    password_confirmation: str = typer.Option(
        ..., prompt="Confirm password", hide_input=True
    ),
):
    response = requests.post(
        f"{API_URL}/auth/register",
        json={
            "name": name,
            "email": email,
            "password": password,
            "password_confirmation": password_confirmation,
        },
        headers={"Content-Type": "application/json", "Accept": "application/json"},
    )
    data = handle_response(response)
    save_token(data["token"])
    console.print(f"[green]Registered and logged in as {data['user']['name']}[/green]")


@app.command()
def logout():
    token = get_token()
    if token:
        requests.post(f"{API_URL}/auth/logout", headers=get_headers())
    clear_token()
    console.print("[green]Logged out[/green]")


@app.command()
def me():
    response = requests.get(f"{API_URL}/auth/me", headers=get_headers())
    user = handle_response(response)
    console.print(Panel(f"Name: {user['name']}\nEmail: {user['email']}", title="Current User"))


@app.command("boards")
def list_boards():
    response = requests.get(f"{API_URL}/boards", headers=get_headers())
    boards = handle_response(response)

    table = Table(title="My Boards")
    table.add_column("ID", style="cyan")
    table.add_column("Name", style="green")
    table.add_column("Description")
    table.add_column("Tasks", justify="right")

    for board in boards:
        table.add_row(
            str(board["id"]),
            board["name"],
            board.get("description", "")[:50] or "-",
            str(len(board.get("tasks", []))),
        )

    console.print(table)


@app.command("board")
def show_board(board_id: int):
    response = requests.get(f"{API_URL}/boards/{board_id}", headers=get_headers())
    board = handle_response(response)

    console.print(Panel(f"{board['name']}\n{board.get('description', '')}", title=f"Board #{board['id']}"))

    for status, title in [("todo", "To Do"), ("in_progress", "In Progress"), ("done", "Done")]:
        tasks = [t for t in board.get("tasks", []) if t["status"] == status]
        if tasks:
            table = Table(title=title)
            table.add_column("ID", style="cyan")
            table.add_column("Title", style="green")
            table.add_column("Description")
            for task in tasks:
                table.add_row(
                    str(task["id"]),
                    task["title"],
                    (task.get("description", "") or "")[:40],
                )
            console.print(table)


@app.command("create-board")
def create_board(
    name: str = typer.Option(..., prompt=True),
    description: str = typer.Option("", prompt=True),
):
    response = requests.post(
        f"{API_URL}/boards",
        json={"name": name, "description": description},
        headers=get_headers(),
    )
    board = handle_response(response, "Board created!")
    console.print(f"Board ID: {board['id']}")


@app.command("delete-board")
def delete_board(board_id: int):
    if typer.confirm(f"Delete board {board_id}?"):
        response = requests.delete(f"{API_URL}/boards/{board_id}", headers=get_headers())
        handle_response(response, "Board deleted!")


@app.command("tasks")
def list_tasks(board_id: int):
    response = requests.get(f"{API_URL}/boards/{board_id}/tasks", headers=get_headers())
    tasks = handle_response(response)

    table = Table(title=f"Tasks in Board #{board_id}")
    table.add_column("ID", style="cyan")
    table.add_column("Title", style="green")
    table.add_column("Status", style="yellow")
    table.add_column("Description")

    for task in tasks:
        table.add_row(
            str(task["id"]),
            task["title"],
            task["status"],
            (task.get("description", "") or "")[:40],
        )

    console.print(table)


@app.command("create-task")
def create_task(
    board_id: int,
    title: str = typer.Option(..., prompt=True),
    description: str = typer.Option("", prompt=True),
    status: str = typer.Option("todo", prompt=True),
):
    response = requests.post(
        f"{API_URL}/boards/{board_id}/tasks",
        json={"title": title, "description": description, "status": status},
        headers=get_headers(),
    )
    task = handle_response(response, "Task created!")
    console.print(f"Task ID: {task['id']}")


@app.command("update-task")
def update_task(
    board_id: int,
    task_id: int,
    title: str = typer.Option(None),
    description: str = typer.Option(None),
    status: str = typer.Option(None),
):
    data = {}
    if title:
        data["title"] = title
    if description:
        data["description"] = description
    if status:
        data["status"] = status

    if not data:
        console.print("[yellow]No updates provided[/yellow]")
        return

    response = requests.patch(
        f"{API_URL}/boards/{board_id}/tasks/{task_id}",
        json=data,
        headers=get_headers(),
    )
    handle_response(response, "Task updated!")


@app.command("delete-task")
def delete_task(board_id: int, task_id: int):
    if typer.confirm(f"Delete task {task_id}?"):
        response = requests.delete(
            f"{API_URL}/boards/{board_id}/tasks/{task_id}",
            headers=get_headers(),
        )
        handle_response(response, "Task deleted!")


@app.command("move-task")
def move_task(
    board_id: int,
    task_id: int,
    status: str = typer.Argument(..., help="New status: todo, in_progress, done"),
):
    if status not in ["todo", "in_progress", "done"]:
        console.print("[red]Invalid status. Use: todo, in_progress, done[/red]")
        raise typer.Exit(1)

    response = requests.patch(
        f"{API_URL}/boards/{board_id}/tasks/{task_id}",
        json={"status": status},
        headers=get_headers(),
    )
    handle_response(response, f"Task moved to {status}!")


@app.command("export")
def export_board(board_id: int):
    response = requests.post(
        f"{API_URL}/boards/{board_id}/export",
        headers=get_headers(),
    )
    data = handle_response(response)
    console.print(f"[green]Export job started![/green]")
    console.print(f"Job ID: {data['job_id']}")
    console.print(f"Status: {data['status']}")


@app.command("job-status")
def job_status(job_id: int):
    response = requests.get(f"{API_URL}/jobs/{job_id}", headers=get_headers())
    job = handle_response(response)

    console.print(Panel(
        f"Status: {job['status']}\n"
        f"Progress: {job['progress']}%\n"
        f"File: {job.get('file_path', '-')}",
        title=f"Job #{job['id']}"
    ))


if __name__ == "__main__":
    app()
